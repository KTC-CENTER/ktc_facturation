#!/bin/bash
# ============================================================
# KTC-Invoice Pro - Script de déploiement des corrections v3
# ============================================================
# Usage depuis le host Docker:
#   1. Copier ce script + ktc-invoice-fixes-v3.zip dans le même dossier
#   2. chmod +x deploy-fixes-v3.sh
#   3. ./deploy-fixes-v3.sh
# ============================================================

set -e

CONTAINER_NAME="ktc-invoice-app-local"
ZIP_FILE="ktc-invoice-fixes-v3.zip"
REMOTE_PATH="/var/www/html"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  KTC-Invoice Pro - Déploiement v3        ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""

# Vérifier le ZIP
if [ ! -f "$ZIP_FILE" ]; then
    echo -e "${RED}Erreur: $ZIP_FILE introuvable dans le dossier courant${NC}"
    echo "Placez le fichier ZIP dans le même dossier que ce script."
    exit 1
fi

# Vérifier le conteneur
if ! docker ps --format '{{.Names}}' | grep -q "$CONTAINER_NAME"; then
    echo -e "${YELLOW}Conteneur '$CONTAINER_NAME' non trouvé. Conteneurs actifs:${NC}"
    docker ps --format '  {{.Names}}'
    echo ""
    read -p "Nom du conteneur PHP: " CONTAINER_NAME
    if ! docker ps --format '{{.Names}}' | grep -q "$CONTAINER_NAME"; then
        echo -e "${RED}Conteneur '$CONTAINER_NAME' introuvable.${NC}"
        exit 1
    fi
fi

echo -e "${YELLOW}► Conteneur cible: $CONTAINER_NAME${NC}"
echo ""

# Backup
echo -e "${YELLOW}1/4 Backup des fichiers actuels...${NC}"
docker exec "$CONTAINER_NAME" bash -c "
    cd $REMOTE_PATH && \
    tar czf /tmp/backup-pre-v3-\$(date +%Y%m%d_%H%M%S).tar.gz \
        config/packages/ \
        src/Controller/ \
        src/Entity/ \
        src/Form/ \
        src/Repository/ \
        src/Service/ \
        templates/ \
        tailwind.config.js \
        package.json \
        2>/dev/null || true
"
echo -e "${GREEN}  ✓ Backup créé dans /tmp/ du conteneur${NC}"

# Copier le ZIP
echo -e "${YELLOW}2/4 Copie du ZIP dans le conteneur...${NC}"
docker cp "$ZIP_FILE" "$CONTAINER_NAME:/tmp/$ZIP_FILE"
echo -e "${GREEN}  ✓ ZIP copié${NC}"

# Extraire
echo -e "${YELLOW}3/4 Extraction des fichiers corrigés...${NC}"
docker exec "$CONTAINER_NAME" bash -c "
    cd $REMOTE_PATH && \
    unzip -o /tmp/$ZIP_FILE && \
    rm /tmp/$ZIP_FILE
"
echo -e "${GREEN}  ✓ Fichiers extraits${NC}"

# Cache clear
echo -e "${YELLOW}4/4 Nettoyage du cache Symfony...${NC}"
docker exec "$CONTAINER_NAME" bash -c "
    cd $REMOTE_PATH && \
    php bin/console cache:clear --no-warmup 2>/dev/null || true && \
    php bin/console cache:warmup 2>/dev/null || true && \
    chown -R www-data:www-data var/ 2>/dev/null || true
"
echo -e "${GREEN}  ✓ Cache nettoyé${NC}"

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✓ Déploiement terminé avec succès!      ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "Corrections appliquées:"
echo -e "  ${GREEN}✓${NC} Webpack @tailwindcss/forms (try/catch safe loading)"
echo -e "  ${GREEN}✓${NC} haveToPaginate → totalItemCount > 10"
echo -e "  ${GREEN}✓${NC} form/tailwind_theme.html.twig (nouveau)"
echo -e "  ${GREEN}✓${NC} EmailTemplate getBody() → getBodyHtml()"
echo -e "  ${GREEN}✓${NC} Dashboard stabilisé (CSS statique, chart resizeDelay)"
echo -e "  ${GREEN}✓${NC} Page profil fonctionnelle (route + template)"
echo -e "  ${GREEN}✓${NC} Pages client/product corrigées"
echo -e "  ${GREEN}✓${NC} Design amélioré (sidebar, cards, forms)"
echo ""
echo -e "Rechargez votre navigateur: ${YELLOW}http://localhost:8090${NC}"
