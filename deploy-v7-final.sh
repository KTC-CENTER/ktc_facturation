#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-fixes-v7-final.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - Deploying v7 Final"
echo "============================================"

if [ ! -f "$ARCHIVE" ]; then echo "❌ Archive $ARCHIVE not found!"; exit 1; fi
if ! docker ps | grep -q "$CONTAINER"; then echo "❌ Container $CONTAINER not running!"; exit 1; fi

echo "📦 Copying archive..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"

echo "📂 Extracting 31 files..."
docker exec "$CONTAINER" bash -c "cd /var/www/html && tar -xzf /tmp/$ARCHIVE --overwrite"

echo "🗑️  Clearing cache..."
docker exec "$CONTAINER" bash -c "cd /var/www/html && rm -rf var/cache/*"

echo "🗃️  Updating database..."
docker exec "$CONTAINER" bash -c "cd /var/www/html && php bin/console doctrine:schema:update --force 2>&1 || true"

echo "🧹 Warming cache..."
docker exec "$CONTAINER" bash -c "cd /var/www/html && php bin/console cache:clear 2>&1 || true"

echo "🧹 Cleanup..."
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "============================================"
echo " ✅ v7 Final Deployed!"
echo "============================================"
echo ""
echo " CORRECTIONS:"
echo "  ✓ WhatsApp: lien absolu inclus dans message"
echo "  ✓ Bouton 'Partager' retiré (uniquement Envoyer)"
echo "  ✓ PDF template = EXACT modèle KTC"
echo "  ✓ BrevoMailerService: getApiInstance() fix"
echo "  ✓ discount/sortOrder null fix"
echo ""
echo " Test: http://localhost:8090"
echo "============================================"
