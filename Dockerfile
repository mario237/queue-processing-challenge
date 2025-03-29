FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    procps

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis &&  pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a new user with specific UID and GID
RUN groupadd -g 1000 www \
    && useradd -u 1000 -ms /bin/bash -g www www

# Create necessary directories
RUN mkdir -p /var/www/html /tmp/supervisor \
    && chown -R www:www /var/www/html /tmp/supervisor

# Copy application files
COPY --chown=www:www . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Install Composer dependencies
RUN composer install --no-interaction --no-scripts

# Prepare Laravel directories
RUN mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/framework/cache \
    && mkdir -p bootstrap/cache \
    && chown -R www:www storage \
    && chown -R www:www bootstrap/cache \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

# Copy Supervisor configuration
COPY .docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY .docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini


# Expose port 9000
EXPOSE 9000

# Set user
USER www

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
