FROM php:8.2-apache

WORKDIR /var/www/html

RUN docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY . .

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
EXPOSE 80
