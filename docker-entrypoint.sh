#!/bin/bash
set -e

# Default to port 80 if PORT is not set
PORT="${PORT:-80}"

# Replace port 80 with the Environment PORT in apache config
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Enable headers module if needed (good for security headers)
a2enmod headers

# Start Apache in foreground
exec docker-php-entrypoint apache2-foreground
