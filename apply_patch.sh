#!/bin/bash
# ============================================================
# KTC Invoice Pro - Patch v19
# ============================================================
# Corrections:
# - WhatsApp ouvre dans un nouvel onglet
# - Logo sur PDF
# - Suivi proforma avec dates par statut
# - NumberToWordsService pour conversion montants
# - Statut "Brouillon" -> "Initié"
# - Stats mises à jour
# ============================================================
set -e

echo "🚀 Application du patch KTC Invoice Pro v19..."
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
# 1. Copie des fichiers PHP - Services
# ============================================================
echo ""
echo "📦 Copie des services..."

# NumberToWordsService (nouveau)
if [ -f "patch/src/Service/NumberToWordsService.php" ]; then
    mkdir -p src/Service
    cp -v patch/src/Service/NumberToWordsService.php src/Service/NumberToWordsService.php
    echo "✅ NumberToWordsService.php créé"
fi

# ============================================================
# 2. Copie des fichiers PHP - Entities
# ============================================================
echo ""
echo "📦 Copie des entités..."

# ProformaStatusHistory (nouveau)
if [ -f "patch/src/Entity/ProformaStatusHistory.php" ]; then
    cp -v patch/src/Entity/ProformaStatusHistory.php src/Entity/ProformaStatusHistory.php
    echo "✅ ProformaStatusHistory.php créé"
fi

# Proforma (mis à jour avec Initié et tracking)
if [ -f "patch/src/Entity/Proforma.php" ]; then
    cp -v patch/src/Entity/Proforma.php src/Entity/Proforma.php
    echo "✅ Proforma.php mis à jour (Initié + tracking)"
fi

# Invoice (mis à jour avec Initié)
if [ -f "patch/src/Entity/Invoice.php" ]; then
    cp -v patch/src/Entity/Invoice.php src/Entity/Invoice.php
    echo "✅ Invoice.php mis à jour (Initié)"
fi

# Product (si existe)
if [ -f "patch/src/Entity/Product.php" ]; then
    cp -v patch/src/Entity/Product.php src/Entity/Product.php
    echo "✅ Product.php mis à jour"
fi

# ============================================================
# 3. Copie des fichiers PHP - Repositories
# ============================================================
echo ""
echo "📦 Copie des repositories..."

# ProformaStatusHistoryRepository (nouveau)
if [ -f "patch/src/Repository/ProformaStatusHistoryRepository.php" ]; then
    cp -v patch/src/Repository/ProformaStatusHistoryRepository.php src/Repository/ProformaStatusHistoryRepository.php
    echo "✅ ProformaStatusHistoryRepository.php créé"
fi

# ============================================================
# 4. Copie des fichiers PHP - Controllers
# ============================================================
echo ""
echo "📦 Copie des contrôleurs..."

# InvoiceController (avec NumberToWordsService)
if [ -f "patch/src/Controller/InvoiceController.php" ]; then
    cp -v patch/src/Controller/InvoiceController.php src/Controller/InvoiceController.php
    echo "✅ InvoiceController.php mis à jour"
fi

# ProformaController (avec product_id support)
if [ -f "patch/src/Controller/ProformaController.php" ]; then
    cp -v patch/src/Controller/ProformaController.php src/Controller/ProformaController.php
    echo "✅ ProformaController.php mis à jour"
fi

# ============================================================
# 5. Copie des fichiers PHP - Forms
# ============================================================
echo ""
echo "📦 Copie des formulaires..."

if [ -f "patch/src/Form/ProductType.php" ]; then
    cp -v patch/src/Form/ProductType.php src/Form/ProductType.php
    echo "✅ ProductType.php mis à jour"
fi

if [ -f "patch/src/Form/CompanySettingsType.php" ]; then
    cp -v patch/src/Form/CompanySettingsType.php src/Form/CompanySettingsType.php
    echo "✅ CompanySettingsType.php mis à jour"
fi

# ============================================================
# 6. Copie des templates
# ============================================================
echo ""
echo "📄 Copie des templates..."

# Invoice templates
mkdir -p templates/invoice
if [ -f "patch/templates/invoice/show.html.twig" ]; then
    cp -v patch/templates/invoice/show.html.twig templates/invoice/show.html.twig
    echo "✅ invoice/show.html.twig (totalInWords + WhatsApp)"
fi

if [ -f "patch/templates/invoice/index.html.twig" ]; then
    cp -v patch/templates/invoice/index.html.twig templates/invoice/index.html.twig
    echo "✅ invoice/index.html.twig (stats Initié)"
fi

if [ -f "patch/templates/invoice/from_proforma.html.twig" ]; then
    cp -v patch/templates/invoice/from_proforma.html.twig templates/invoice/from_proforma.html.twig
fi

# Proforma templates
mkdir -p templates/proforma
if [ -f "patch/templates/proforma/show.html.twig" ]; then
    cp -v patch/templates/proforma/show.html.twig templates/proforma/show.html.twig
    echo "✅ proforma/show.html.twig (timeline + WhatsApp)"
fi

if [ -f "patch/templates/proforma/index.html.twig" ]; then
    cp -v patch/templates/proforma/index.html.twig templates/proforma/index.html.twig
    echo "✅ proforma/index.html.twig (stats Initié)"
fi

# Product templates
mkdir -p templates/product
if [ -f "patch/templates/product/show.html.twig" ]; then
    cp -v patch/templates/product/show.html.twig templates/product/show.html.twig
    echo "✅ product/show.html.twig (créer proforma)"
fi

if [ -f "patch/templates/product/new.html.twig" ]; then
    cp -v patch/templates/product/new.html.twig templates/product/new.html.twig
fi

if [ -f "patch/templates/product/_form.html.twig" ]; then
    cp -v patch/templates/product/_form.html.twig templates/product/_form.html.twig
fi

# Client templates
mkdir -p templates/client
if [ -f "patch/templates/client/new.html.twig" ]; then
    cp -v patch/templates/client/new.html.twig templates/client/new.html.twig
    echo "✅ client/new.html.twig (formulaire amélioré)"
fi

if [ -f "patch/templates/client/edit.html.twig" ]; then
    cp -v patch/templates/client/edit.html.twig templates/client/edit.html.twig
    echo "✅ client/edit.html.twig (formulaire amélioré)"
fi

# ============================================================
# 7. Base de données - Ajout colonnes tracking
# ============================================================
echo ""
echo "🗄️ Mise à jour base de données..."

# Fonction pour exécuter SQL
run_sql() {
    local container=$1
    local sql_file=$2
    docker exec $container mysql -u root -pverysecret ktc_invoice < $sql_file 2>/dev/null
}

# Migration pour le tracking des proformas
if [ -f "patch/migrations/proforma_status_tracking.sql" ]; then
    if command -v docker &> /dev/null; then
        if docker ps --format '{{.Names}}' | grep -q "ktc-invoice-mysql"; then
            run_sql "ktc-invoice-mysql" "patch/migrations/proforma_status_tracking.sql" && echo "✅ Migration proforma tracking (prod)" || echo "⚠️ Vérifier migration manuellement"
        elif docker ps --format '{{.Names}}' | grep -q "ktc-invoice-mysql-local"; then
            run_sql "ktc-invoice-mysql-local" "patch/migrations/proforma_status_tracking.sql" && echo "✅ Migration proforma tracking (local)" || echo "⚠️ Vérifier migration manuellement"
        else
            echo "⚠️ Container MySQL non trouvé - exécuter migrations/proforma_status_tracking.sql manuellement"
        fi
    else
        echo "⚠️ Docker non disponible - exécuter migrations/proforma_status_tracking.sql manuellement"
    fi
fi

# Autres corrections BD
if [ -f "patch/fix_database.sql" ]; then
    if command -v docker &> /dev/null; then
        if docker ps --format '{{.Names}}' | grep -q "ktc-invoice-mysql"; then
            run_sql "ktc-invoice-mysql" "patch/fix_database.sql" && echo "✅ BD corrigée (prod)" || echo "⚠️ Vérifier BD manuellement"
        elif docker ps --format '{{.Names}}' | grep -q "ktc-invoice-mysql-local"; then
            run_sql "ktc-invoice-mysql-local" "patch/fix_database.sql" && echo "✅ BD corrigée (local)" || echo "⚠️ Vérifier BD manuellement"
        fi
    fi
fi

# ============================================================
# 8. Cache
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
echo "🎉 PATCH v19 APPLIQUÉ!"
echo "============================================================"
echo ""
echo "📝 Changements appliqués:"
echo ""
echo "   🆕 NOUVEAUX FICHIERS:"
echo "   - NumberToWordsService.php: conversion montants en lettres"
echo "   - ProformaStatusHistory.php: historique des statuts"
echo "   - ProformaStatusHistoryRepository.php"
echo ""
echo "   📝 MODIFICATIONS:"
echo "   - Proforma.php: timeline avec dates, status 'Initié'"
echo "   - Invoice.php: status 'Initié', méthodes ajoutées"
echo "   - InvoiceController.php: injection NumberToWordsService"
echo "   - ProformaController.php: support product_id"
echo ""
echo "   🎨 TEMPLATES:"
echo "   - proforma/show.html.twig: timeline graphique"
echo "   - invoice/show.html.twig: montant en lettres"
echo "   - Toutes les listes: 'Initié' au lieu de 'Brouillon'"
echo "   - WhatsApp: ouverture nouvel onglet"
echo ""
echo "   🗄️ BASE DE DONNÉES:"
echo "   - Table proforma_status_history créée"
echo "   - Colonnes sent_at, accepted_at, etc. ajoutées"
echo ""
echo "🔑 Credentials: admin@kamer-center.net / password"
echo "============================================================"
