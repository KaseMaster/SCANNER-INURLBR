FROM php:8.3-cli

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libcurl4-openssl-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    curl \
    mbstring \
    xml \
    pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /app

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chmod +x bin/inurlbr && \
    chown -R www-data:www-data /app/output

# Switch to non-root user
USER www-data

# Default command
ENTRYPOINT ["php", "bin/inurlbr"]
CMD ["--help"]
