FROM php:8.4-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY . /var/www/html/

RUN mkdir -p /var/www/html/Uploads/Books /var/www/html/Uploads/Profiles \
    && chown -R www-data:www-data /var/www/html/Uploads \
    && chmod -R 775 /var/www/html/Uploads

EXPOSE 80
