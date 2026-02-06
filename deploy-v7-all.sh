#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v7-all-fixes.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - v7 All Fixes"
echo "============================================"

if [ ! -f "$ARCHIVE" ]; then echo "❌ Archive not found!"; exit 1; fi
if ! docker ps | grep -q "$CONTAINER"; then echo "❌ Container not running!"; exit 1; fi

echo "📦 Deploying all fixes..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"
docker exec "$CONTAINER" bash -c "cd /var/www/html && tar -xzf /tmp/$ARCHIVE --overwrite"
docker exec "$CONTAINER" bash -c "cd /var/www/html && rm -rf var/cache/*"
docker exec "$CONTAINER" bash -c "cd /var/www/html && php bin/console cache:clear 2>&1 || true"
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "============================================"
echo " ✅ v7 All Fixes Deployed!"
echo "============================================"
echo ""
echo " CORRECTIONS:"
echo "  ✓ getTotalPriceFloat() -> getTotalFloat()"
echo "  ✓ Route payment GET+POST"
echo "  ✓ TVA défaut = 0% (optionnelle)"
echo "  ✓ PDF propres avec moins de couleurs"
echo "  ✓ WhatsApp lien absolu"
echo "  ✓ Bouton Partager retiré"
echo ""
echo " MODÈLES PROFORMA:"
echo "  → Créer un modèle avec tous les produits"
echo "  → Lors de création proforma: ?template=ID"
echo "  → Tous les produits sont pré-remplis"
echo "  → Seul le client reste à choisir"
echo ""
echo " Test: http://localhost:8090"
