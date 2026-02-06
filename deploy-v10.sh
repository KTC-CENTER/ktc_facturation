#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v10-template-load.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - v10 Template Auto-Load"
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
echo " ✅ v10 Template Auto-Load Deployed!"
echo "============================================"
echo ""
echo " NOUVELLE FONCTIONNALITÉ:"
echo ""
echo " 📋 Chargement automatique des produits du modèle:"
echo ""
echo "   1. Nouvelle proforma → Sélectionner un modèle"
echo "   2. Les produits se chargent automatiquement!"
echo "   3. Objet, conditions et notes pré-remplis"
echo "   4. Sélectionner 'Aucun modèle' = saisie manuelle"
echo ""
echo " Test: http://localhost:8090"
