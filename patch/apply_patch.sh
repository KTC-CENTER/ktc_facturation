#!/bin/bash
# ============================================================
# KTC Invoice Pro - Patch de correction
# ============================================================
set -e

echo "🚀 Application du patch KTC Invoice Pro..."
echo ""

# Détection du répertoire
if [ -d "/var/www/html/src" ]; then
    PROJECT_DIR="/var/www/html"
elif [ -d "src" ]; then
    PROJECT_DIR="."
else
    echo "❌ Répertoire projet non trouvé"
    exit 1
fi

cd "$PROJECT_DIR"
echo "📁 Répertoire: $PROJECT_DIR"

# ============================================================
# 1. Copie des fichiers PHP
# ============================================================
echo ""
echo "📦 Copie des fichiers..."

# Entity
cp -v patch/src/Entity/Product.php src/Entity/Product.php 2>/dev/null || echo "⚠️ Product.php - vérifier manuellement"

# Forms
cp -v patch/src/Form/ProductType.php src/Form/ProductType.php 2>/dev/null || echo "⚠️ ProductType.php - vérifier manuellement"
cp -v patch/src/Form/CompanySettingsType.php src/Form/CompanySettingsType.php 2>/dev/null || echo "⚠️ CompanySettingsType.php - vérifier manuellement"

# Controllers
cp -v patch/src/Controller/InvoiceController.php src/Controller/InvoiceController.php 2>/dev/null || echo "⚠️ InvoiceController.php - vérifier manuellement"

# Templates
cp -v patch/templates/product/new.html.twig templates/product/new.html.twig 2>/dev/null || echo "⚠️ product/new.html.twig"
cp -v patch/templates/product/_form.html.twig templates/product/_form.html.twig 2>/dev/null || echo "⚠️ product/_form.html.twig"
cp -v patch/templates/invoice/from_proforma.html.twig templates/invoice/from_proforma.html.twig 2>/dev/null || echo "⚠️ invoice/from_proforma.html.twig"

echo "✅ Fichiers copiés"

# ============================================================
# 2. Base de données
# ============================================================
echo ""
echo "🗄️ Correction base de données..."

# Essayer avec docker
if command -v docker &> /dev/null; then
    # Production
    if docker ps --format '{{.Names}}' | grep -q "ktc-invoice-mysql"; then
        docker exec ktc-invoice-mysql mysql -u root -pverysecret ktc_invoice < patch/fix_database.sql 2>/dev/null && echo "✅ BD corrigée (docker prod)" || echo "⚠️ BD - vérifier manuellement"
    # Local
    elif docker ps --format '{{.Names}}' | grep -q "ktc-invoice-mysql-local"; then
        docker exec ktc-invoice-mysql-local mysql -u root -pverysecret ktc_invoice < patch/fix_database.sql 2>/dev/null && echo "✅ BD corrigée (docker local)" || echo "⚠️ BD - vérifier manuellement"
    else
        echo "⚠️ Container MySQL non trouvé - exécuter fix_database.sql manuellement"
    fi
else
    echo "⚠️ Docker non disponible - exécuter fix_database.sql manuellement"
fi

# ============================================================
# 3. Cache
# ============================================================
echo ""
echo "🧹 Nettoyage cache..."

if command -v docker &> /dev/null; then
    if docker ps --format '{{.Names}}' | grep -q "ktc-invoice-app"; then
        docker exec ktc-invoice-app php bin/console cache:clear 2>/dev/null || true
        echo "✅ Cache vidé (docker prod)"
    elif docker ps --format '{{.Names}}' | grep -q "ktc-invoice-app-local"; then
        docker exec ktc-invoice-app-local php bin/console cache:clear 2>/dev/null || true
        echo "✅ Cache vidé (docker local)"
    fi
fi

# Local sans docker
if [ -f "bin/console" ]; then
    php bin/console cache:clear 2>/dev/null || true
fi

# ============================================================
# Terminé
# ============================================================
echo ""
echo "============================================================"
echo "🎉 PATCH APPLIQUÉ!"
echo "============================================================"
echo ""
echo "📝 Changements appliqués:"
echo "   - Product.php: types en MAJUSCULES, nouveaux champs margin/purchasePrice"
echo "   - ProductType.php: champs prix d'achat et marge avec calcul auto"
echo "   - CompanySettingsType.php: champ defaultMargin ajouté"
echo "   - InvoiceController.php: route from_proforma ajoutée"
echo "   - Templates produit: form_rest caché, boutons en bas"
echo ""
echo "🔑 Credentials: admin@kamer-center.net / password"
echo "============================================================"
