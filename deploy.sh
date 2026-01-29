#!/bin/bash
# Script de déploiement vers GitHub
# KTC-Invoice Pro

REPO_URL="https://github.com/KTC-CENTER/ktc_facturation.git"
BRANCH="main"

echo "=== Déploiement KTC-Invoice Pro vers GitHub ==="
echo ""

# Vérifier si git est installé
if ! command -v git &> /dev/null; then
    echo "❌ Git n'est pas installé. Installez-le d'abord."
    exit 1
fi

# Initialiser git si nécessaire
if [ ! -d ".git" ]; then
    echo "📁 Initialisation du dépôt git..."
    git init
    git config user.email "dev@ktc-center.com"
    git config user.name "KTC-CENTER"
fi

# Ajouter tous les fichiers
echo "📦 Ajout des fichiers..."
git add .

# Créer le commit
echo "💾 Création du commit..."
git commit -m "Initial commit - KTC-Invoice Pro v1.0

Application de gestion de facturation professionnelle
- Symfony 6.4 + PHP 8.2
- Docker Compose
- Gestion clients, produits, proformas, factures
- Génération PDF, envoi email (Brevo), partage WhatsApp
- Multi-rôles: SUPER_ADMIN, ADMIN, COMMERCIAL, VIEWER
- Interface Tailwind CSS + Alpine.js" 2>/dev/null || echo "Commit déjà existant ou rien à committer"

# Renommer la branche en main
git branch -M $BRANCH

# Ajouter le remote si nécessaire
if ! git remote | grep -q "origin"; then
    echo "🔗 Configuration du remote..."
    git remote add origin $REPO_URL
else
    git remote set-url origin $REPO_URL
fi

# Pousser vers GitHub
echo "🚀 Push vers GitHub..."
git push -u origin $BRANCH --force

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Déploiement réussi !"
    echo "📍 URL: $REPO_URL"
else
    echo ""
    echo "❌ Erreur lors du push. Vérifiez vos credentials."
    echo ""
    echo "Si vous avez une erreur d'authentification, utilisez :"
    echo "git remote set-url origin https://YOUR_TOKEN@github.com/KTC-CENTER/ktc_facturation.git"
fi
