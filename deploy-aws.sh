#!/bin/bash

# AWS Production Deployment Script (Without Docker)
# This script prepares your Laravel application for production deployment on AWS

set -e  # Exit on any error

echo "🚀 Starting AWS Production Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# 1. Check for .env file
if [ ! -f .env ]; then
    print_error ".env file not found!"
    print_info "Creating .env from .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
        print_error "Please configure your .env file with production credentials before continuing!"
        exit 1
    else
        print_error ".env.example file not found! Cannot proceed."
        exit 1
    fi
fi

# 2. Check if APP_KEY is set
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    print_info "APP_KEY not set. Generating application key..."
    php artisan key:generate --force
    print_success "Application key generated"
fi

# 3. Install/Update Composer dependencies (production only)
print_info "Installing Composer dependencies (production mode)..."
composer install --no-dev --optimize-autoloader --no-interaction
print_success "Composer dependencies installed"

# 4. Install/Update NPM dependencies and build assets
if [ -f package.json ]; then
    print_info "Installing NPM dependencies (including dev dependencies for building)..."
    npm ci
    print_info "Building production assets..."
    npm run build
    print_info "Cleaning up dev dependencies (optional)..."
    # Note: We keep node_modules for potential future builds
    # If you want to remove dev dependencies after build, uncomment:
    # npm prune --production
    print_success "Assets built successfully"
fi

# 5. Test database connection
print_info "Testing database connection..."
if php artisan db:show &>/dev/null; then
    print_success "Database connection successful"
else
    print_error "Database connection failed! Please check your .env file."
    exit 1
fi

# 6. Run database migrations
print_info "Running database migrations..."
php artisan migrate --force
print_success "Database migrations completed"

# 7. Clear and cache configuration
print_info "Clearing old caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

print_info "Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Configuration cached"

# 8. Link storage
print_info "Linking storage..."
php artisan storage:link || print_info "Storage link already exists or failed (non-critical)"
print_success "Storage linked"

# 9. Set proper permissions
print_info "Setting directory permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 755 public
print_success "Permissions set"

# 10. Create necessary directories if they don't exist
print_info "Ensuring required directories exist..."
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
print_success "Directories verified"

# 11. Set ownership (adjust user/group as needed for your server)
# Uncomment and modify the following line based on your server setup
# chown -R www-data:www-data storage bootstrap/cache

# 12. Optimize autoloader
print_info "Optimizing autoloader..."
composer dump-autoload --optimize --classmap-authoritative
print_success "Autoloader optimized"

# 13. Display deployment summary
echo ""
print_success "Deployment completed successfully!"
echo ""
print_info "Next steps:"
echo "  1. Ensure your web server (Apache/Nginx) is configured correctly"
echo "  2. Set up a queue worker: php artisan queue:work --daemon"
echo "  3. Set up a scheduler (cron): * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1"
echo "  4. Monitor logs: tail -f storage/logs/laravel.log"
echo ""
