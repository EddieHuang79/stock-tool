FROM laradock/php-fpm:2.2-7.2

RUN mkdir /app
COPY stockTool /app

#RUN apt-get update \
#    && apt-get install -y \
#        telnet \
#        net-tools \
#        curl \
#        wget \
#        iputils-ping \
#        vim \
#        php7.2 \
#        php7.2-fpm \
#        php7.2-xml \
#        php7.2-mbstring \
#        zip \
#        unzip \
#        php7.2-pgsql \
#        php7.2-mysql \
#        php7.2-zip \
#        php7.2-curl \
#        php7.2-ldap \
#        php7.2-gd
#RUN mkdir /app
#COPY app /app/app
#COPY bootstrap /app/bootstrap
#COPY config /app/config
#COPY database /app/database
#COPY public /app/public
#COPY resources /app/resources
#COPY routes /app/routes
#COPY storage /app/storage
#COPY tests /app/tests
#COPY vendor /app/vendor
#COPY .env /app/.env
#COPY artisan /app/artisan
#COPY composer.json /app/composer.json
#COPY composer.lock /app/composer.lock
#COPY server.php /app/server.php
#COPY webpack.mix.js /app/webpack.mix.js
#CMD [ "/bin/bash", "-c", "tail -f /dev/null" ]