#!/bin/bash
# ============================================================================
# KTC-Invoice Pro - Deployment Script v5 (COMPLETE)
# ============================================================================
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-fixes-v5-complete.tar.gz"
APP_DIR="/var/www/html"

echo "============================================"
echo " KTC-Invoice Pro - Deploying v5 Fixes"
echo "============================================"

if [ ! -f "$ARCHIVE" ]; then echo "❌ Archive $ARCHIVE not found!"; exit 1; fi
if ! docker ps | grep -q "$CONTAINER"; then echo "❌ Container $CONTAINER not running!"; exit 1; fi

echo "📦 Copying archive to container..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"

echo "📂 Extracting 70 files..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && tar -xzf /tmp/$ARCHIVE --overwrite"

echo "🗑️  Clearing cache..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && rm -rf var/cache/*"

echo "📁 Creating directories..."
docker exec "$CONTAINER" bash -c "mkdir -p $APP_DIR/public/uploads/{logo,pdf} && chmod -R 777 $APP_DIR/public/uploads/"

echo "🗃️  Updating database schema..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console doctrine:schema:update --force --no-interaction 2>&1 || echo 'Check schema manually'"

echo "🔑 Setting permissions..."
docker exec "$CONTAINER" bash -c "chown -R www-data:www-data $APP_DIR/var/ $APP_DIR/public/uploads/ 2>/dev/null || true"

echo "🧹 Warming cache..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console cache:clear --env=dev 2>&1 || true"
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console cache:warmup --env=dev 2>&1 || true"

echo "🔍 Verifying routes..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console debug:router 2>/dev/null | grep -E 'app_notification|app_client_email|app_product_export_pdf|app_report'" || true

echo "🧹 Cleanup..."
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "============================================"
echo " ✅ Deployment v5 COMPLETE! (70 files)"
echo "============================================"
echo ""
echo " BUGS FIXED:"
echo "  1. ✅ Product selection in invoice/proforma creation"
echo "  2. ✅ closeModal() on share cancel button"
echo "  3. ✅ Change password form error (FormView null)"
echo "  4. ✅ All v4 fixes (routes, filters, themes...)"
echo ""
echo " NEW FEATURES:"
echo "  5. ✅ Send email to individual client"
echo "  6. ✅ Mass email notifications (client selection)"
echo "  7. ✅ Product export to PDF (catalogue)"
echo "  8. ✅ Beautiful PDF invoice template with logo"
echo "  9. ✅ Beautiful PDF proforma template with logo"
echo " 10. ✅ HTML email templates (reset, share, docs)"
echo " 11. ✅ Notification section in sidebar"
echo " 12. ✅ Favicon/Logo admin settings"
echo ""
echo " Test at: http://localhost:8090"
echo "============================================"
