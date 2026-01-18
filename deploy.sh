#!/bin/bash

# Production Deployment Helper Script

# Ensure script halts on error
set -e

# Load environment variables if .env exists
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | awk '/=/ {print $1}')
fi

echo "🚀 Starting Deployment Process..."

# 1. Check for .env file
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "👉 Please replicate .env.example to .env and configure production credentials."
    exit 1
fi

# 2. Build Docker Images
echo "📦 Building Docker images..."
docker-compose -f docker-compose.prod.yml build

# 3. Start Services
echo "🔄 Starting services..."
docker-compose -f docker-compose.prod.yml up -d

# 4. Wait for DB to be ready (simple sleep, ideal would be healthcheck wait)
echo "⏳ Waiting for database to initialize (10s)..."
sleep 10

# 5. Run Migrations
echo "🗄️ Running database migrations..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# 6. Optimize/Cache
echo "⚡ Optimizing application..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache

# 7. Link Storage
echo "🔗 Linking storage..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan storage:link || true

echo "✅ Deployment Complete! System is live."
echo "📜 Logs can be viewed with: docker-compose -f docker-compose.prod.yml logs -f"
