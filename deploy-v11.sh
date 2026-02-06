#!/bin/bash
set -e

CONTAINER="ktc-invoice-app-local"
ARCHIVE="ktc-v11-complete.tar.gz"

echo "============================================"
echo " KTC-Invoice Pro - v11 Complete"
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
echo " ✅ v11 Complete Deployed!"
echo "============================================"
echo ""
echo " CORRECTIONS:"
echo "  ✓ Création/édition modèles avec produits"
echo "  ✓ Chargement quantités/prix du modèle"
echo "  ✓ 'Aucun modèle' réinitialise les produits"
echo "  ✓ PDF marges augmentées (15mm)"
echo "  ✓ PDF produits avec en-tête entreprise"
echo ""
echo " FONCTIONNEMENT MODÈLES:"
echo ""
echo "  1. Modèles → Nouveau"
echo "     - Nom, description, conditions"
echo "     - Ajouter produits avec qté et prix"
echo ""
echo "  2. Proformas → Nouvelle"
echo "     - Choisir un modèle = produits chargés"
echo "     - Choisir 'Aucun modèle' = vide"
echo ""
echo " Test: http://localhost:8090"
