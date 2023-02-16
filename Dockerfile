FROM php:8.1-apache

ENV APP_DATABASE_HOST=127.0.0.1
ENV APP_DATABASE_PORT=3306
ENV APP_DATABASE_NAME=akeneo_pim
ENV APP_DATABASE_USER=akeneo
ENV APP_DATABASE_PASSWORD=S3CR3T
ENV APP_ENV=dev
ENV APP_DEBUG=1
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV NO_DOCKER=true

RUN apt update

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions
RUN install-php-extensions apcu
RUN install-php-extensions bcmath
RUN install-php-extensions exif
RUN install-php-extensions gd
RUN install-php-extensions imagick
RUN install-php-extensions intl
RUN install-php-extensions zip
RUN install-php-extensions pdo_mysql
RUN install-php-extensions @composer

# Install Elasticsearch
RUN apt update && apt install -y apt-transport-https wget gnupg
RUN wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | apt-key add -
RUN echo "deb https://artifacts.elastic.co/packages/7.x/apt stable main" | tee -a /etc/apt/sources.list.d/elastic-7.x.list
RUN apt update && apt-get install -y elasticsearch
RUN service elasticsearch start
#RUN sysctl -w vm.max_map_count=262144
RUN echo "vm.max_map_count=262144" | tee /etc/sysctl.d/elasticsearch.conf
RUN service elasticsearch restart

# Install MySQL Server
RUN apt update && apt install -y mariadb-server
RUN service mariadb start && \
    mysql -e "CREATE DATABASE akeneo_pim;" && \
    mysql -e "CREATE USER akeneo@127.0.0.1 IDENTIFIED BY 'S3CR3T';" && \
    mysql -e "GRANT ALL PRIVILEGES ON akeneo_pim.* TO akeneo@127.0.0.1;"

#Mount Data Volume
VOLUME /var/lib/mysql

RUN composer create-project --prefer-dist akeneo/pim-community-standard /var/www/akeneo "6"

RUN mv /var/www/akeneo/* /var/www

RUN chown www-data:www-data /var/www && \
    a2enmod rewrite && \
    service apache2 restart

EXPOSE 80

# Install Akeneo PIM
RUN apt update && apt install -y yarn yarnpkg
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash
RUN apt install -y nodejs

RUN cd /var/www && make

#
#COPY .docker/akeneo/apache/apache.conf /etc/apache2/sites-enabled/000-default.conf
#COPY .docker/akeneo/entrypoint.sh /entrypoint.sh
#
#WORKDIR /var/www
#
#COPY .docker/akeneo/.env var/www/.env
#
#RUN chmod +x /entrypoint.sh
#
#ENTRYPOINT ["/entrypoint.sh"]
