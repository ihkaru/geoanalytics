# Gunakan base image PHP 8.2 (atau sesuaikan versi Anda)
FROM php:8.4-fpm-alpine

# Copy custom php.ini to increase memory limit
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Instal ekstensi PHP yang dibutuhkan Laravel + PostGIS
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    gd \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev

# Konfigurasi dan instal ekstensi
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    bcmath \
    exif

# Instal Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Ganti ownership ke www-data (user yang menjalankan php-fpm)
# Ini penting agar Laravel bisa menulis ke storage/logs
RUN chown -R www-data:www-data /var/www/html

USER www-data

# Expose port default php-fpm
EXPOSE 9000