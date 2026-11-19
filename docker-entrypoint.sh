#!/bin/bash
set -e

# Default to Render's port 10000 or fallback to 80
PORT="${PORT:-10000}"

echo "==> Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure required directories have proper write permissions
mkdir -p /var/www/html/database /var/www/html/api/config
chown -R www-data:www-data /var/www/html/database /var/www/html/api/config
chmod -R 777 /var/www/html/database /var/www/html/api/config

echo "==> Starting Apache web server on port ${PORT}..."
exec apache2-foreground
