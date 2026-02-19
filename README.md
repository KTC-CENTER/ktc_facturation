# KTC-Invoice Pro

Application web professionnelle de gestion de facturation développée avec Symfony 6.4.

## 🎯 Fonctionnalités

- **Gestion des utilisateurs** : Authentification sécurisée avec rôles (SUPER_ADMIN, ADMIN, COMMERCIAL, VIEWER)
- **Gestion des clients** : CRUD complet avec historique des documents
- **Catalogue produits** : Trois types (LOGICIEL, MATÉRIEL, SERVICE) avec caractéristiques spécifiques
- **Modèles préconfigurés** : Templates réutilisables pour générer rapidement des proformas
- **Proformas/Devis** : Création, suivi des statuts, conversion en facture
- **Factures** : Numérotation légale, suivi des paiements
- **Génération PDF** : Documents professionnels avec en-tête personnalisable
- **Partage documents** : Email (Brevo), WhatsApp, liens sécurisés avec QR Code
- **Dashboard & Rapports** : KPIs, graphiques, filtrage par période/type/commercial

## 🛠️ Stack Technique

- **Backend** : PHP 8.2+, Symfony 6.4, Doctrine ORM
- **Base de données** : MySQL 8.0
- **Frontend** : Twig, Tailwind CSS 3, Alpine.js, Stimulus, Turbo
- **PDF** : Dompdf
- **Email** : Brevo (ex-Sendinblue) API v3
- **QR Code** : endroid/qr-code
- **Conteneurisation** : Docker & Docker Compose

## 📋 Prérequis

- Docker & Docker Compose
- Git

## 🚀 Installation

### Installation rapide (recommandée)

```bash
git clone https://github.com/KTC-CENTER/ktc_facturation.git
cd ktc_facturation
make install
```

C'est tout ! L'application est accessible sur http://localhost:8080

### Installation manuelle

#### 1. Cloner le projet

```bash
git clone https://github.com/KTC-CENTER/ktc_facturation.git
cd ktc_facturation
```

#### 2. Configurer l'environnement

```bash
cp .env.example .env
# Éditer .env avec vos configurations (API Brevo, etc.)
```

#### 3. Lancer les conteneurs Docker

```bash
docker compose up -d --build
```

#### 4. Installer les dépendances

```bash
# PHP
docker compose exec ktc-invoice-app composer install

# Les assets sont compilés automatiquement par le conteneur node
```

#### 5. Créer la base de données

```bash
docker compose exec ktc-invoice-app php bin/console doctrine:database:create --if-not-exists
docker compose exec ktc-invoice-app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec ktc-invoice-app php bin/console doctrine:fixtures:load --no-interaction
```

#### 6. Accéder à l'application

- **Application** : http://localhost:8080
- **phpMyAdmin** : http://localhost:8081
- **MailHog** : http://localhost:8025

## 🔧 Commandes Make utiles

```bash
make help           # Affiche toutes les commandes disponibles
make start          # Démarre les conteneurs
make stop           # Arrête les conteneurs
make restart        # Redémarre les conteneurs
make logs           # Affiche les logs
make shell          # Accède au shell PHP
make db-migrate     # Exécute les migrations
make db-fixtures    # Charge les fixtures
make db-reset       # Reset complet de la base
make cache-clear    # Vide le cache
make test           # Lance les tests
```

## 🔄 Hot-Reload (Développement)

Les modifications sont automatiquement prises en compte :
- **PHP/Twig** : Rechargez simplement la page
- **CSS/JS** : Le conteneur `node` compile automatiquement en mode watch

Pour voir les logs de compilation :
```bash
make logs-node
```

## 👤 Comptes par défaut (fixtures)

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@ktc-center.com | admin123 | SUPER_ADMIN |
| commercial@ktc-center.com | commercial123 | COMMERCIAL |
| viewer@ktc-center.com | viewer123 | VIEWER |

## 📁 Structure du projet

```
ktc-invoice-pro/
├── assets/              # CSS, JavaScript, Stimulus controllers
├── config/              # Configuration Symfony
├── docker/              # Dockerfiles et configurations
├── migrations/          # Migrations Doctrine
├── public/              # Point d'entrée, assets compilés
├── src/
│   ├── Controller/      # Contrôleurs
│   ├── Entity/          # Entités Doctrine
│   ├── EventSubscriber/ # Event subscribers
│   ├── Form/            # Formulaires
│   ├── Repository/      # Repositories
│   ├── Security/        # Authentification
│   └── Service/         # Services métier
├── templates/           # Templates Twig
├── tests/               # Tests
├── .env                 # Variables d'environnement
├── composer.json        # Dépendances PHP
├── docker-compose.yml   # Configuration Docker
├── package.json         # Dépendances JavaScript
└── webpack.config.js    # Configuration Webpack Encore
```

## 🔧 Commandes utiles

### Docker

```bash
# Démarrer les conteneurs
docker compose up -d

# Arrêter les conteneurs
docker compose down

# Voir les logs
docker compose logs -f ktc-invoice-app

# Accéder au conteneur PHP
docker compose exec ktc-invoice-app bash
```

### Symfony

```bash
# Vider le cache
docker compose exec ktc-invoice-app php bin/console cache:clear

# Créer une migration
docker compose exec ktc-invoice-app php bin/console make:migration

# Exécuter les migrations
docker compose exec ktc-invoice-app php bin/console doctrine:migrations:migrate

# Charger les fixtures
docker compose exec ktc-invoice-app php bin/console doctrine:fixtures:load
```

### Assets

```bash
# Build développement
docker compose run --rm node npm run dev

# Build production
docker compose run --rm node npm run build

# Watch mode
docker compose run --rm node npm run watch
```

## 🧪 Tests

```bash
# Exécuter tous les tests
docker compose exec ktc-invoice-app php bin/phpunit

# Tests avec couverture
docker compose exec ktc-invoice-app php bin/phpunit --coverage-html coverage
```

## 📧 Configuration Email (Brevo)

1. Créer un compte sur [Brevo](https://www.brevo.com/)
2. Générer une clé API dans les paramètres
3. Configurer dans `.env` :

```env
BREVO_API_KEY=votre_cle_api
BREVO_SENDER_EMAIL=noreply@votre-domaine.com
BREVO_SENDER_NAME="KTC-Center"
```

## 🔐 Sécurité

- Mots de passe hashés avec bcrypt
- Protection CSRF sur tous les formulaires
- Sessions sécurisées avec durée de vie limitée
- Rôles hiérarchiques pour le contrôle d'accès
- Liens de partage avec expiration et tokens uniques

## 📊 Localisation

- **Timezone** : Africa/Douala
- **Langue** : Français
- **Devise** : FCFA
- **TVA** : 19.25%

## 🤝 Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## 📝 Licence

Propriétaire - KTC-Center Sarl © 2026

## 📞 Support

- **Email** : support@ktc-center.com
- **Téléphone** : +237 XXX XXX XXX
