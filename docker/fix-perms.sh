#!/bin/bash
# Fix permissions before Apache starts
export APACHE_RUN_USER=www-data
export APACHE_RUN_GROUP=www-data
export APACHE_RUN_DIR=/var/run/apache2
export APACHE_PID_FILE=/var/run/apache2/apache2.pid
export APACHE_LOCK_DIR=/var/lock/apache2

mkdir -p /var/run/apache2 /var/lock/apache2

chown -R www-data:www-data /var/www
chmod -R 777 /var/www
chmod 666 /var/www/html/database.sqlite 2>/dev/null || true

exec /usr/sbin/apache2 -D FOREGROUND
