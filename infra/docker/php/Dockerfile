FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    supervisor \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libxml2-dev \
    libonig-dev \
    default-mysql-client

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    bcmath \
    intl \
    zip \
    opcache

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

COPY infra/docker/php/php.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]