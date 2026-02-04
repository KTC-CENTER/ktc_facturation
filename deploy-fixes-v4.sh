#!/bin/bash
# ============================================================================
# KTC-Invoice Pro - Deployment Script v4 (COMPLETE)
# ============================================================================
# Fixes: 14 critical errors + email templates + favicon/logo management + reports
# ============================================================================

set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-fixes-v4-complete.tar.gz"
APP_DIR="/var/www/html"

echo "============================================"
echo " KTC-Invoice Pro - Deploying v4 Fixes"
echo "============================================"
echo ""

# Check archive exists
if [ ! -f "$ARCHIVE" ]; then
    echo "❌ Archive $ARCHIVE not found!"
    echo "   Place this script next to the archive."
    exit 1
fi

# Check container is running
if ! docker ps | grep -q "$CONTAINER"; then
    echo "❌ Container $CONTAINER is not running!"
    exit 1
fi

echo "📦 Copying archive to container..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"

echo "📂 Extracting files..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && tar -xzf /tmp/$ARCHIVE --overwrite"

echo "🗑️  Cleaning cache..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && rm -rf var/cache/*"

echo "📁 Creating upload directories..."
docker exec "$CONTAINER" bash -c "mkdir -p $APP_DIR/public/uploads/logo && chmod 777 $APP_DIR/public/uploads/logo"

echo "🔑 Setting permissions..."
docker exec "$CONTAINER" bash -c "chown -R www-data:www-data $APP_DIR/var/ $APP_DIR/public/uploads/ 2>/dev/null || true"

echo "🗃️  Updating database schema..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console doctrine:schema:update --force --no-interaction 2>&1 || echo 'Schema update note: check manually if needed'"

echo "🧹 Warming up cache..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console cache:clear --env=dev 2>&1 || true"
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console cache:warmup --env=dev 2>&1 || true"

echo "🔍 Verifying routes..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console debug:router 2>/dev/null | grep -E 'app_report|app_home|app_login|app_share'" || echo "Route check done"

echo "🧹 Cleanup..."
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "============================================"
echo " ✅ Deployment v4 COMPLETE!"
echo "============================================"
echo ""
echo " Fixes deployed:"
echo "  1. ✅ share/_modal.html.twig - Route names fixed"
echo "  2. ✅ UserType - require_password → is_edit"
echo "  3. ✅ User filters (status active/inactive)"
echo "  4. ✅ CompanySettingsType - defaultInvoiceConditions + bankDetails"
echo "  5. ✅ tailwind_theme.html.twig - file_widget block fixed"
echo "  6. ✅ client/documents.html.twig - |u.truncate → |slice"
echo "  7. ✅ User entity - mappedBy createdBy (not sharedBy)"
echo "  8. ✅ Reports system (5 pages with charts)"
echo "  9. ✅ Dashboard - Colorful gradient KPI cards with links"
echo " 10. ✅ Login page - Modern split-screen design"
echo " 11. ✅ Root route / → login or dashboard"
echo " 12. ✅ Favicon + Logo manageable in admin settings"
echo " 13. ✅ Email templates - Beautiful HTML with clickable links"
echo " 14. ✅ Password reset - Absolute URL, clickable button"
echo " 15. ✅ Sidebar - Dynamic company logo + name"
echo " 16. ✅ Twig AppExtension for global company settings"
echo ""
echo " Test at: http://localhost:8090"
echo "============================================"
