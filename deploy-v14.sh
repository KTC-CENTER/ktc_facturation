#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v14-complete.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - v14 Complete"
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
echo " ✅ v14 Complete Deployed!"
echo "============================================"
echo ""
echo " CORRECTIONS:"
echo ""
echo "  ✓ Erreur modification produit corrigée"
echo "  ✓ Champ isActive ajouté à ProductType"
echo "  ✓ PDF body margin: 25px"
echo "  ✓ Logo entreprise sur PDF (si défini)"
echo "  ✓ Couleur douce #5B8BA0"
echo ""
echo " Test: http://localhost:8090"
echo ""
echo "============================================"
echo " 🔧 Pour VPS (ERR_CONNECTION_REFUSED):"
echo "============================================"
echo ""
echo " Exécuter sur le VPS:"
echo " ssh root@81.169.177.240 'bash -s' < vps-diagnose.sh"
echo ""
echo " Ou manuellement:"
echo " 1. docker ps -a"
echo " 2. docker logs ktc-invoice-app --tail 50"
echo " 3. docker logs ktc-invoice-nginx --tail 50"
echo " 4. cd /opt/apps/ktc-invoice"
echo " 5. docker compose -f docker-compose.prod.yml down"
echo " 6. docker compose -f docker-compose.prod.yml up -d"
