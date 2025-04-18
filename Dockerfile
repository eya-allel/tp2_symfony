FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    default-mysql-client \
    libicu-dev

# Installation des extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql zip intl

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]