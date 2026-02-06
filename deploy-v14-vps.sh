#!/bin/bash
set -e

echo "============================================"
echo " KTC-Invoice Pro - v14 VPS Fix"
echo "============================================"
echo ""
echo " Ce package corrige:"
echo "  ✓ PHP-FPM slowlog permission error"
echo "  ✓ Nginx upstream not found"
echo "  ✓ Logo PDF non affiché"
echo "  ✓ http2 directive deprecated"
echo "  ✓ Formulaire produit (isActive)"
echo "  ✓ PDF margins"
echo ""
echo "============================================"
echo ""
echo " INSTRUCTIONS POUR LE VPS:"
echo ""
echo " 1. Connectez-vous au VPS:"
echo "    ssh root@81.169.177.240"
echo ""
echo " 2. Arrêtez les conteneurs:"
echo "    cd /opt/apps/ktc-invoice"
echo "    docker compose -f docker-compose.prod.yml down"
echo ""
echo " 3. Mettez à jour le code:"
echo "    git fetch origin main"
echo "    git reset --hard origin/main"
echo ""
echo " 4. Reconstruisez les conteneurs:"
echo "    docker compose -f docker-compose.prod.yml build --no-cache"
echo ""
echo " 5. Démarrez les conteneurs:"
echo "    docker compose -f docker-compose.prod.yml up -d"
echo ""
echo " 6. Vérifiez que tout fonctionne:"
echo "    docker ps -a"
echo "    docker logs ktc-invoice-app --tail 50"
echo ""
echo " OU utilisez le script de réparation automatique:"
echo "    chmod +x vps-fix.sh"
echo "    ./vps-fix.sh"
echo ""
echo "============================================"
echo ""
echo " Pour déployer en local:"
echo ""

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v14-vps-fix.tar.gz"

if [ ! -f "$ARCHIVE" ]; then 
    echo "❌ Archive $ARCHIVE not found!"
    exit 1
fi

if ! docker ps | grep -q "$CONTAINER" 2>/dev/null; then 
    echo "⚠️ Container local non trouvé. Déploiement VPS uniquement."
    exit 0
fi

echo "📦 Deploying to local container..."
docker cp "$ARCHIVE" "$CONTAINER:/tmp/$ARCHIVE"
docker exec "$CONTAINER" bash -c "cd /var/www/html && tar -xzf /tmp/$ARCHIVE --overwrite"
docker exec "$CONTAINER" bash -c "cd /var/www/html && rm -rf var/cache/*"
docker exec "$CONTAINER" bash -c "cd /var/www/html && php bin/console cache:clear 2>&1 || true"
docker exec "$CONTAINER" rm -f "/tmp/$ARCHIVE"

echo ""
echo "✅ Local deployment complete!"
echo " Test: http://localhost:8090"
