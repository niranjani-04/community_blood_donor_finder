FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    dos2unix \
    && docker-php-ext-install pdo_mysql mbstring gd

# Enable Apache Rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . /var/www/html/

# Handle permissions
RUN chown -R www-data:www-data /var/www/html

# Set up the start script
COPY start.sh /usr/local/bin/start.sh
RUN dos2unix /usr/local/bin/start.sh && \
    chmod +x /usr/local/bin/start.sh

# Start using the script
CMD ["/usr/local/bin/start.sh"]
