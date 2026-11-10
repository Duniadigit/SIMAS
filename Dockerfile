# ==========================================
# Stage 1: Build Frontend Assets (Vite)
# ==========================================
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# ==========================================
# Stage 2: PHP-FPM Application (Laravel 12)
# ==========================================
FROM php:8.2-fpm

ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies sistem Linux
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Konfigurasi & pasang ekstensi PHP (termasuk GD untuk TTD/FPDI)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Pasang Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Konfigurasi custom PHP (upload limit, memory)
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

WORKDIR /var/www

# Salin source code project
COPY . /var/www

# Salin aset frontend hasil build dari Stage 1
COPY --from=frontend /app/public/build /var/www/public/build

# Pasang dependensi PHP (production mode)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Entrypoint script untuk permission & storage:link
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
