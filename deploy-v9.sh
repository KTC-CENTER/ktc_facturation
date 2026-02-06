#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v9-complete.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - v9 Complete"
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
echo " ✅ v9 Complete Deployed!"
echo "============================================"
echo ""
echo " CORRECTIONS:"
echo "  ✓ getTotalFloat() fixed"
echo "  ✓ Payment fields (method, reference)"
echo "  ✓ TVA défaut = 0%"
echo "  ✓ PDF propres"
echo ""
echo " NOUVELLES FONCTIONNALITÉS:"
echo ""
echo " 📋 PROFORMA - Deux façons de créer:"
echo "  1. 'Créer de zéro' - Saisir tous les détails"
echo "  2. 'Depuis un modèle' - Produits pré-remplis"
echo "     → Sélectionner le modèle"
echo "     → Choisir le client"
echo "     → C'est prêt!"
echo ""
echo " 🧾 FACTURE - Deux façons de créer:"
echo "  1. 'Créer de zéro' - Saisir tous les détails"
echo "  2. 'Depuis une proforma' - Convertir proforma"
echo ""
echo " Test: http://localhost:8090"
