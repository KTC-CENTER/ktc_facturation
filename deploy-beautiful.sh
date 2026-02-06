#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v7-beautiful-pdf.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - Beautiful PDF v7"
echo "============================================"

if [ ! -f "$ARCHIVE" ]; then echo "❌ Archive not found!"; exit 1; fi
if ! docker ps | grep -q "$CONTAINER"; then echo "❌ Container not running!"; exit 1; fi

echo "📦 Deploying beautiful templates..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"
docker exec "$CONTAINER" bash -c "cd /var/www/html && tar -xzf /tmp/$ARCHIVE --overwrite"
docker exec "$CONTAINER" bash -c "cd /var/www/html && rm -rf var/cache/* && php bin/console cache:clear 2>&1"
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "============================================"
echo " ✅ Beautiful PDFs Deployed!"
echo "============================================"
echo ""
echo " Features:"
echo "  ✓ Header avec logo + slogan bleu"
echo "  ✓ Titre 'Proposition commerciale' rouge"
echo "  ✓ Box émetteur avec bordure bleue arrondie"
echo "  ✓ Nom client en gros bleu majuscules"
echo "  ✓ Banner objet gradient bleu avec ombre"
echo "  ✓ Tableau avec header gradient bleu"
echo "  ✓ Lignes alternées pour lisibilité"
echo "  ✓ Total en gradient bleu bold"
echo "  ✓ Montant en lettres souligné orange"
echo "  ✓ Conditions avec puces rouges"
echo "  ✓ Zones signature professionnelles"
echo "  ✓ Footer gradient bleu avec RCCM"
echo "  ✓ Watermark BROUILLON/PAYÉE/ANNULÉE"
echo ""
echo " Test: http://localhost:8090"
