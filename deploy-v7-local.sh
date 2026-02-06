#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-fixes-v7.tar.gz"
APP_DIR="/var/www/html"

echo "============================================"
echo " KTC-Invoice Pro - Deploying v7 Fixes"
echo "============================================"

if [ ! -f "$ARCHIVE" ]; then echo "❌ Archive $ARCHIVE not found!"; exit 1; fi
if ! docker ps | grep -q "$CONTAINER"; then echo "❌ Container $CONTAINER not running!"; exit 1; fi

echo "📦 Copying archive..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"

echo "📂 Extracting files..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && tar -xzf /tmp/$ARCHIVE --overwrite"

echo "🗑️  Clearing cache..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && rm -rf var/cache/*"

echo "📁 Creating directories..."
docker exec "$CONTAINER" bash -c "mkdir -p $APP_DIR/public/uploads/{logo,pdf} && chmod -R 777 $APP_DIR/public/uploads/"

echo "🗃️  Updating database..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console doctrine:schema:update --force 2>&1 || true"

echo "🧹 Warming cache..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console cache:clear 2>&1 || true"

echo "🧹 Cleanup..."
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "============================================"
echo " ✅ v7 Deployed Successfully!"
echo "============================================"
echo ""
echo " FIXES:"
echo "  • BrevoMailerService: emailsApi → getApiInstance()"
echo "  • PDF templates: Nouveau design professionnel"
echo "  • ProformaController: getDefaultConditions()"
echo "  • discount/sortOrder: empty_data '0'"
echo ""
echo " Test: http://localhost:8090"
echo "============================================"
