# ============================================================
# Stage 1: Composer Dependencies
# ============================================================
FROM composer:2.7 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize


# ============================================================
# Stage 2: Build Frontend Assets (Node.js)
# Butuh vendor/ karena Vite perlu tightenco/ziggy
# ============================================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy package files first for better cache utilization
COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

# Copy source files
COPY resources/ ./resources/
COPY vite.config.js ./
COPY public/ ./public/

# Copy vendor dari composer stage (diperlukan oleh Ziggy)
COPY --from=composer-builder /app/vendor ./vendor

RUN npm run build


# ============================================================
# Stage 3: PHP Application (Production)
# ============================================================
FROM php:8.3-fpm-alpine AS app

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    freetype-dev \
    libjpeg-turbo-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy vendor dari composer stage (tidak perlu install ulang)
COPY --from=composer-builder /app/vendor ./vendor

# Copy built frontend assets dari stage 2
COPY --from=frontend-builder /app/public/build ./public/build

# Set permissions
RUN mkdir -p public/covers \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
        public/covers \
        /var/lib/nginx \
        /var/log/nginx \
    && chmod -R 775 storage bootstrap/cache public/covers

# Buat direktori untuk Supervisor log
RUN mkdir -p /var/log/supervisor

# Copy configs
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
