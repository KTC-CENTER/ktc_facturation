#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v12-complete.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - v12 Complete"
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
echo " ✅ v12 Complete Deployed!"
echo "============================================"
echo ""
echo " CORRECTIONS:"
echo ""
echo "  ✓ TVA non obligatoire dans le formulaire"
echo "  ✓ Quantités/prix chargés depuis le modèle"
echo "  ✓ 'Aucun modèle' réinitialise les produits"
echo "  ✓ Lien vers facture depuis proforma"
echo "  ✓ PDF avec marges correctes (20mm)"
echo ""
echo " PROFORMA SHOW:"
echo "  - Si facture générée: lien direct"
echo "  - Sinon: bouton 'Générer la facture'"
echo ""
echo " Test: http://localhost:8090"
