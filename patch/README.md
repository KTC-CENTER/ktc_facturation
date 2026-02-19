# KTC Invoice Pro - Patch de correction complet

## Problèmes corrigés

### 1. Formulaire produit amélioré
- **Unité** : Liste déroulante avec choix prédéfinis (unité, pièce, licence, forfait, heure, jour, etc.)
- **Catégorie** : Liste déroulante avec catégories courantes + catégories existantes de la BD
- **Disposition** : Layout en 3 colonnes avec sections claires
- **form_rest()** : Placé AVANT les boutons (évite les champs qui apparaissent après)
- **Calcul automatique** : Prix vente = Prix achat × (1 + Marge/100)

### 2. Marge par défaut dans les paramètres
- Champ `defaultMargin` affiché dans la section "Devise, TVA et Marge"
- Template settings/invoicing.html.twig corrigé avec form_rest avant boutons

### 3. Créer proforma depuis un produit
- Bouton "Créer une proforma" sur la page détail du produit
- Lien avec `product_id` pour pré-remplir le formulaire
- Le produit, son prix et quantité 1 sont automatiquement ajoutés

### 4. Route `app_invoice_from_proforma` manquante
- Route ajoutée dans InvoiceController
- Template invoice/from_proforma.html.twig créé

### 5. Types produit en majuscules
- Constantes Product gardées en MAJUSCULES (compatibilité données existantes)
- TYPE_LOGICIEL = 'LOGICIEL' (pas 'logiciel')

### 6. Erreur `updated_at` Data truncated
- Colonne `updated_at` nullable dans Product

### 7. Formulaire proforma
- FCFA uniquement dans les totaux (pas au-dessus des champs)
- Structure TABLE pour les lignes de document
- data-attributes sur les produits pour JavaScript

## Installation

### Méthode 1: Script automatique

```bash
cd /var/www/html
unzip ktc-patch.zip
chmod +x patch/apply_patch.sh
./patch/apply_patch.sh
```

### Méthode 2: Manuelle

```bash
# 1. Copier les fichiers PHP
cp patch/src/Entity/Product.php src/Entity/Product.php
cp patch/src/Form/ProductType.php src/Form/ProductType.php
cp patch/src/Form/CompanySettingsType.php src/Form/CompanySettingsType.php
cp patch/src/Form/DocumentItemType.php src/Form/DocumentItemType.php
cp patch/src/Controller/InvoiceController.php src/Controller/InvoiceController.php

# 2. Copier les templates
cp patch/templates/product/*.twig templates/product/
cp patch/templates/settings/invoicing.html.twig templates/settings/
cp patch/templates/proforma/*.twig templates/proforma/
cp patch/templates/invoice/from_proforma.html.twig templates/invoice/

# 3. Exécuter le SQL
docker exec ktc-invoice-mysql-local mysql -u root -pverysecret ktc_invoice < patch/fix_database.sql

# 4. Vider le cache
docker exec ktc-invoice-app-local php bin/console cache:clear
```

## Fichiers inclus

```
patch/
├── src/
│   ├── Entity/
│   │   └── Product.php              # Types MAJUSCULES + nouveaux champs
│   ├── Form/
│   │   ├── ProductType.php          # Unit/Category en ChoiceType
│   │   ├── CompanySettingsType.php  # Avec defaultMargin
│   │   └── DocumentItemType.php     # Avec data-attributes
│   └── Controller/
│       └── InvoiceController.php    # Route from_proforma
├── templates/
│   ├── product/
│   │   ├── new.html.twig            # Utilise _form.html.twig
│   │   ├── edit.html.twig           # Utilise _form.html.twig
│   │   ├── show.html.twig           # Bouton "Créer proforma"
│   │   └── _form.html.twig          # Formulaire amélioré
│   ├── settings/
│   │   └── invoicing.html.twig      # defaultMargin affiché
│   ├── proforma/
│   │   ├── _form.html.twig          # Sans FCFA sur les inputs
│   │   └── _item_row.html.twig      # Structure TABLE
│   └── invoice/
│       └── from_proforma.html.twig
├── fix_database.sql                 # Corrections BD
├── apply_patch.sh                   # Script d'installation
└── README.md
```

## Fonctionnalités clés

### Calcul automatique du prix de vente
Dans le formulaire produit, saisissez :
1. Le prix d'achat
2. La marge souhaitée (%)
3. Cliquez "Calculer automatiquement" ou laissez le calcul se faire en temps réel

**Formule** : Prix vente = Prix achat × (1 + Marge/100)

### Créer une proforma depuis un produit
1. Allez sur la page détail d'un produit
2. Cliquez sur "Créer une proforma" (bouton bleu)
3. Le formulaire proforma s'ouvre avec le produit déjà ajouté (prix et quantité 1)

### Unités disponibles
- Unité, Pièce, Licence, Forfait
- Heure, Jour, Mois, Année
- Lot, Kg, Mètre

### Catégories disponibles
- Logiciels de gestion, Logiciels comptables
- Sécurité informatique, Matériel informatique
- Périphériques, Réseaux
- Formation, Maintenance, Installation
- Conseil, Support technique
- + catégories existantes de votre base de données

## Credentials

- **Email**: admin@kamer-center.net
- **Mot de passe**: password

## Support

En cas de problème, vérifier :
1. Les logs Symfony : `var/log/dev.log`
2. Les erreurs Docker : `docker logs ktc-invoice-app-local`
3. Que tous les fichiers ont bien été copiés
