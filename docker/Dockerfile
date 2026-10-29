FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    msmtp \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Route PHP mail() through msmtp -> Mailhog (dev mail catcher)
RUN echo "sendmail_path = /usr/bin/msmtp -t --host=mailhog --port=1025" > /usr/local/etc/php/conf.d/mail.ini

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
