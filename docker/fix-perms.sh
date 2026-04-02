#!/bin/bash
# Fix permissions before Apache starts
chown -R www-data:www-data /var/www
chmod -R 777 /var/www
chmod 666 /var/www/html/database.sqlite 2>/dev/null || true

exec /usr/sbin/apache2 -D FOREGROUND
