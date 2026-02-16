FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring gd

# Enable Apache Rewrite
RUN a2enmod rewrite

# Copy application code
COPY . /var/www/html/
WORKDIR /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Dynamic Port Configuration & Start Apache
# uses sed to replace port 80 with $PORT in the configurations
CMD sh -c "sed -i \"s/Listen 80/Listen \${PORT:-80}/g\" /etc/apache2/ports.conf && \
    sed -i \"s/<VirtualHost \*:80>/<VirtualHost *:\${PORT:-80}>/g\" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground"
