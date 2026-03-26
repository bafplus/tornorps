#!/bin/bash
set -e

echo "=== TornOps Container Starting ==="

DATA_DIR="${DATA_DIR:-/data}"
APP_DIR="${DATA_DIR}/app"

git config --global --add safe.directory /data/app
git config --global --add safe.directory /var/www/html

# If no /data mounted, use /var/www/html directly
if [ ! -d "$APP_DIR/.git" ]; then
    if [ -d "/data/app/.git" ]; then
        APP_DIR="/data/app"
        echo "Using /data/app"
    else
        echo "First run: Cloning repository..."
        if [ -d "/data" ]; then
            git clone https://github.com/bafplus/tornops.git "$APP_DIR"
        else
            git clone https://github.com/bafplus/tornops.git /var/www/html
            echo "No /data volume - using /var/www/html directly"
        fi
    fi
else
    echo "Repository already exists at $APP_DIR"
    if [ -d "$APP_DIR/.git" ]; then
        echo "Updating repository..."
        cd "$APP_DIR"
        git pull origin main || true
    fi
fi

if [ "$APP_DIR" != "/var/www/html" ]; then
    cd "$APP_DIR"
    if [ -L /var/www/html ] || [ -d /var/www/html ]; then
        rm -rf /var/www/html
    fi
    # Copy files instead of symlink for better compatibility
    cp -r . /var/www/html/
fi

# Always work in /var/www/html for the web app
cd /var/www/html

# Create storage directories that are gitignored
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Fix permissions for Apache - ensure all files are readable and executable
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage storage/framework storage/logs bootstrap/cache
chown -R 33:33 .

chmod -R 775 storage/framework
chown -R www-data:www-data storage/framework

# Fix permissions on /data first if it exists (for TrueNAS mounted volumes)
if [ -d "$DATA_DIR" ]; then
    echo "Fixing /data permissions..."
    chown -R 33:33 "$DATA_DIR"
    chmod -R 777 "$DATA_DIR"
fi

# Use /data/.env if mounted, or use environment variables passed to container
if [ -f "${DATA_DIR}/.env" ] && [ -d "$DATA_DIR" ]; then
    echo "Using .env from data directory..."
    cp "${DATA_DIR}/.env" .env
    
    # Force correct database path for /data volume
    sed -i 's|DB_DATABASE=.*|DB_DATABASE=/data/database.sqlite|' .env
    
    grep -q "^SESSION_DRIVER=" .env || echo "SESSION_DRIVER=file" >> .env
    grep -q "^CACHE_STORE=" .env || echo "CACHE_STORE=file" >> .env
    
    DB_PATH="/data/database.sqlite"
else
    echo "Using environment variables..."
    # Use env vars passed to container
    cat > .env << ENVEOF
APP_NAME="${APP_NAME:-TornOps}"
APP_ENV="${APP_ENV:-production}"
APP_KEY=
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost:8080}"
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
TORN_API_KEY=${TORN_API_KEY:-dummy}
ENVEOF
    DB_PATH="/var/www/html/database.sqlite"
fi

if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing dependencies..."
    composer install --no-interaction --no-dev
fi

# Ensure database directory exists
mkdir -p "$(dirname "$DB_PATH")"

if [ ! -f "$DB_PATH" ]; then
    echo "Creating database at $DB_PATH..."
    touch "$DB_PATH"
fi

chmod 666 "$DB_PATH"
chown 33:33 "$DB_PATH"

# Set Apache to run as www-data
export APACHE_RUN_USER=www-data
export APACHE_RUN_GROUP=www-data

php artisan key:generate --force 2>/dev/null || true
php artisan migrate --force

echo "Setting up cron..."
echo "* * * * * root /usr/local/bin/php /var/www/html/artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel-scheduler
chmod 0644 /etc/cron.d/laravel-scheduler

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf