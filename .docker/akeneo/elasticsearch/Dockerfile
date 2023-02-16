FROM elastic/elasticsearch:8.4.2

WORKDIR /usr/share/elasticsearch

# Install ES plugins
RUN bin/elasticsearch-plugin install analysis-phonetic
RUN bin/elasticsearch-plugin install analysis-icu
