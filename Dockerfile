FROM php:8.2-apache

# Install MySQL extensions (PDO and MySQLi)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache Rewrite Module (often needed for routing)
RUN a2enmod rewrite

# Copy application source code to the web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for Railway
EXPOSE 80
