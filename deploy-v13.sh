#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v13-complete.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - v13 Complete"
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
echo " ✅ v13 Complete Deployed!"
echo "============================================"
echo ""
echo " CORRECTIONS:"
echo ""
echo "  ✓ Erreur TVA null corrigée (empty_data)"
echo "  ✓ Statut proforma modifiable (boutons)"
echo "  ✓ Génération facture depuis proforma"
echo "  ✓ Quantités du modèle chargées (JS fix)"
echo "  ✓ PDF couleur douce (#5B8BA0)"
echo "  ✓ PDF espaces réduits"
echo "  ✓ PDF total plus compact"
echo ""
echo " WORKFLOW STATUT PROFORMA:"
echo ""
echo "  DRAFT → [Marquer envoyée] → SENT"
echo "  DRAFT/SENT → [Acceptée] → ACCEPTED"
echo "  DRAFT/SENT → [Refusée] → REFUSED"
echo "  (any) → [Générer facture] → INVOICED"
echo ""
echo " Test: http://localhost:8090"
