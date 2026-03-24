FROM php:8.4-cli-alpine

# Instalar dependencias necesarias para PostgreSQL y extensiones PHP
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    linux-headers \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app