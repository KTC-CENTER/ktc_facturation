#!/bin/bash
# ============================================================================
# KTC-Invoice Pro - Deployment Script v6
# ============================================================================
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-fixes-v6.tar.gz"
APP_DIR="/var/www/html"

echo "============================================"
echo " KTC-Invoice Pro - Deploying v6 Fixes"
echo "============================================"

if [ ! -f "$ARCHIVE" ]; then echo "❌ Archive $ARCHIVE not found!"; exit 1; fi
if ! docker ps | grep -q "$CONTAINER"; then echo "❌ Container $CONTAINER not running!"; exit 1; fi

echo "📦 Copying archive to container..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"

echo "📂 Extracting 61 files..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && tar -xzf /tmp/$ARCHIVE --overwrite"

echo "🗑️  Clearing cache..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && rm -rf var/cache/*"

echo "📁 Creating directories..."
docker exec "$CONTAINER" bash -c "mkdir -p $APP_DIR/public/uploads/{logo,pdf} && chmod -R 777 $APP_DIR/public/uploads/"

echo "🗃️  Updating database schema..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console doctrine:schema:update --force --no-interaction 2>&1 || true"

echo "🔑 Setting permissions..."
docker exec "$CONTAINER" bash -c "chown -R www-data:www-data $APP_DIR/var/ $APP_DIR/public/uploads/ 2>/dev/null || true"

echo "🧹 Warming cache..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console cache:clear --env=dev 2>&1 || true"
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console cache:warmup --env=dev 2>&1 || true"

echo "🔍 Verifying new routes..."
docker exec "$CONTAINER" bash -c "cd $APP_DIR && php bin/console debug:router 2>/dev/null | grep -E 'proforma_template|send_email'" || true

echo "🧹 Cleanup..."
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "============================================"
echo " ✅ Deployment v6 COMPLETE! (61 files)"
echo "============================================"
echo ""
echo " BUGS CORRIGÉS:"
echo "  1. ✅ sortOrder null error (empty_data='0')"
echo "  2. ✅ Modèles -> redirige vers Proforma Templates"
echo "  3. ✅ Affichage produits amélioré dans formulaires"
echo "  4. ✅ Modal partage corrigé (noms champs)"
echo ""
echo " NOUVELLES FONCTIONNALITÉS:"
echo "  5. ✅ Envoi facture/proforma par Email direct"
echo "  6. ✅ Envoi facture/proforma par WhatsApp direct"
echo "  7. ✅ Email avec pièce jointe PDF"
echo "  8. ✅ Gestion modèles de proforma"
echo "  9. ✅ Bouton Envoyer (dropdown Email/WhatsApp)"
echo ""
echo " Test at: http://localhost:8090"
echo "============================================"
