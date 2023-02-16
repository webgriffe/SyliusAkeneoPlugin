FROM php:8.1-apache

RUN a2enmod rewrite

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

COPY apache/apache.conf /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www

RUN composer create-project --prefer-dist akeneo/pim-community-standard /var/www/akeneo "7@stable"

RUN mv /var/www/akeneo/* /var/www

COPY php/php.ini /usr/local/etc/php/conf.d/

RUN apt update && apt install -y apt-transport-https wget gnupg
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt update && apt -y install yarn

RUN curl -sL https://deb.nodesource.com/setup_16.x | bash
RUN apt install -y nodejs

RUN apt-get update \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y ssl-cert \
 && rm -r /var/lib/apt/lists/*

RUN NO_DOCKER=true make dev

COPY .env /var/www/.env

RUN mkdir var
RUN chown -R www-data:www-data var

RUN bin/console pim:installer:db --catalog vendor/akeneo/pim-community-dev/src/Akeneo/Platform/Bundle/InstallerBundle/Resources/fixtures/icecat_demo_dev

CMD ["apache2-foreground"]

ENTRYPOINT ["/entrypoint.sh"]
