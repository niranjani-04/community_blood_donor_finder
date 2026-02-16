#!/bin/bash
set -e

# Default to port 80 if PORT is not set
PORT=${PORT:-80}

echo "Configuring Apache to listen on port $PORT..."

# Replace port 80 in ports.conf
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf

# Replace port 80 in the default site config
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf

echo "Starting Apache..."
exec apache2-foreground
