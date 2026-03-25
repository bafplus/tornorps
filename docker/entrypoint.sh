#!/bin/bash
set -e

echo "=== TornOps Container Starting ==="

DATA_DIR="${DATA_DIR:-/data}"
APP_DIR="${DATA_DIR}/app"

if [ ! -d "$APP_DIR/.git" ]; then
    echo "First run: Cloning repository..."
    git clone https://github.com/bafplus/tornorps.git "$APP_DIR"
else
    echo "Repository already exists at $APP_DIR"
fi

cd "$APP_DIR"

if [ -L /var/www/html ] || [ -d /var/www/html ]; then
    rm -rf /var/www/html
fi
ln -sf "$APP_DIR" /var/www/html

chmod -R 775 "$APP_DIR/storage"
chown -R www-data:www-data "$APP_DIR/storage"

mkdir -p "$APP_DIR/storage/framework/cache" "$APP_DIR/storage/framework/sessions" "$APP_DIR/storage/framework/views"
chmod -R 775 "$APP_DIR/storage/framework"
chown -R www-data:www-data "$APP_DIR/storage/framework"

if [ -f "${DATA_DIR}/.env" ]; then
    echo "Using .env from data directory..."
    cp "${DATA_DIR}/.env" .env
    
    # Ensure required fields exist
    grep -q "^SESSION_DRIVER=" .env || echo "SESSION_DRIVER=file" >> .env
    grep -q "^CACHE_STORE=" .env || echo "CACHE_STORE=file" >> .env
else
    echo "Creating default .env..."
    cat > .env << 'ENVEOF'
APP_NAME="TornOps"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:8080
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
DB_DATABASE=/data/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
TORN_API_KEY=dummy
ENVEOF
fi

if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing dependencies..."
    composer install --no-interaction --no-dev
fi

if [ ! -f "/data/database.sqlite" ]; then
    echo "Creating database..."
    touch /data/database.sqlite
fi

chmod 666 /data/database.sqlite

php artisan key:generate --force 2>/dev/null || true
php artisan migrate --force

echo "Setting up cron..."
echo "* * * * * root /usr/local/bin/php ${APP_DIR}/artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel-scheduler
chmod 0644 /etc/cron.d/laravel-scheduler

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf