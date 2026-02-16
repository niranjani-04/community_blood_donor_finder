FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    dos2unix \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring gd

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html/

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/

# Fix permissions and line endings (CRLF to LF) for the script
RUN dos2unix /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh && \
    chown -R www-data:www-data /var/www/html

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
