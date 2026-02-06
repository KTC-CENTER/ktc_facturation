#!/bin/bash
# Script de réparation VPS pour KTC-Invoice

echo "=========================================="
echo " KTC-Invoice VPS Fix Script"
echo "=========================================="

APP_DIR="/opt/apps/ktc-invoice"
cd "$APP_DIR"

echo ""
echo "1. Stopping all containers..."
docker compose -f docker-compose.prod.yml down

echo ""
echo "2. Removing old images..."
docker rmi $(docker images -q ktc-invoice* 2>/dev/null) 2>/dev/null || true

echo ""
echo "3. Pulling latest code..."
git fetch origin main
git reset --hard origin/main

echo ""
echo "4. Rebuilding containers..."
docker compose -f docker-compose.prod.yml build --no-cache

echo ""
echo "5. Starting containers..."
docker compose -f docker-compose.prod.yml up -d

echo ""
echo "6. Waiting for containers to start..."
sleep 20

echo ""
echo "7. Checking container status..."
docker ps -a

echo ""
echo "8. Checking app logs..."
docker logs ktc-invoice-app --tail 20 2>&1

echo ""
echo "9. Checking nginx logs..."
docker logs ktc-invoice-nginx --tail 10 2>&1

echo ""
echo "10. Running migrations..."
docker exec ktc-invoice-app php bin/console doctrine:schema:update --force 2>&1 || true

echo ""
echo "11. Clearing cache..."
docker exec ktc-invoice-app php bin/console cache:clear --env=prod 2>&1 || true

echo ""
echo "12. Setting permissions..."
docker exec ktc-invoice-app chown -R www-data:www-data var public/uploads 2>&1 || true

echo ""
echo "=========================================="
echo " Testing site..."
echo "=========================================="
curl -I https://facturation.kamer-center.net 2>&1 | head -5

echo ""
echo "Done!"
