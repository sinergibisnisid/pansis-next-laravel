FROM php:8.4-fpm-alpine

LABEL maintainer="PANSIN ACCESS Team"

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    postgresql-dev \
    linux-headers \
    supervisor \
    shadow \
    bash \
    libwebp-dev \
    oniguruma-dev \
    autoconf \
    gcc \
    g++ \
    make

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    sockets \
    mbstring \
    opcache \
    exif

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install MQTT extension (php-mqtt via composer, not pecl)
# We install the sockets extension above which is required for MQTT

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create system user
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Set working directory
WORKDIR /var/www

# Copy application files
COPY --chown=www:www . /var/www

# Install composer dependencies if composer.json exists
RUN if [ -f "composer.json" ]; then \
    composer install --no-dev --optimize-autoloader --no-interaction --no-progress; \
    fi

# Set permissions
RUN chown -R www:www /var/www && \
    chmod -R 755 /var/www/storage 2>/dev/null || true && \
    chmod -R 755 /var/www/bootstrap/cache 2>/dev/null || true

# Copy supervisor config
COPY docker/php/supervisord.conf /etc/supervisord.conf

# Switch to non-root user
USER www

# Expose PHP-FPM port
EXPOSE 9000

CMD ["php-fpm"]
