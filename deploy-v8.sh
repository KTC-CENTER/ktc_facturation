#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v8-payment-fix.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - v8 Payment Fix"
echo "============================================"

if [ ! -f "$ARCHIVE" ]; then echo "❌ Archive not found!"; exit 1; fi
if ! docker ps | grep -q "$CONTAINER"; then echo "❌ Container not running!"; exit 1; fi

echo "📦 Deploying files..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"
docker exec "$CONTAINER" bash -c "cd /var/www/html && tar -xzf /tmp/$ARCHIVE --overwrite"

echo "🗄️ Running database migration..."
docker exec "$CONTAINER" bash -c "cd /var/www/html && php bin/console doctrine:schema:update --force 2>&1 || true"

echo "🔄 Clearing cache..."
docker exec "$CONTAINER" bash -c "cd /var/www/html && rm -rf var/cache/*"
docker exec "$CONTAINER" bash -c "cd /var/www/html && php bin/console cache:clear 2>&1 || true"
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "============================================"
echo " ✅ v8 Payment Fix Deployed!"
echo "============================================"
echo ""
echo " CORRECTIONS:"
echo "  ✓ Ajout champs paymentMethod et paymentReference"
echo "  ✓ Route payment GET+POST"
echo "  ✓ Template payment.html.twig"
echo "  ✓ TVA défaut = 0%"
echo "  ✓ PDF propres"
echo ""
echo " Test: http://localhost:8090"
