FROM php:8.4-fpm

# Install system dependencies and clear cache in one step
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    jpegoptim optipng pngquant gifsicle \
    vim \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    libexif-dev \
    libonig-dev && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring zip exif pcntl gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create a non-root user for the Laravel application
RUN groupadd -g 1001 www && \
    useradd -u 1001 -m -g www -s /bin/bash www

# Set working directory
WORKDIR /var/www

# Pre-create Laravel application directory with proper ownership
RUN mkdir -p /var/www/application/vendor/composer && \
    chown -R www:www /var/www/application

# Change to non-root user for security
USER www

# Optionally copy your local files (useful in production)
COPY --chown=www:www . /var/www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
