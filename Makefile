# KTC-Invoice Pro - Makefile
# ===========================

.PHONY: help install start stop restart build logs shell db-migrate db-fixtures db-reset cache-clear test

# Variables
DOCKER_COMPOSE = docker compose
PHP_CONTAINER = ktc-invoice-app-local
NODE_CONTAINER = ktc-invoice-node

# Couleurs
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

## Aide
help: ## Affiche cette aide
	@echo ''
	@echo '${GREEN}KTC-Invoice Pro${RESET}'
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  ${YELLOW}%-15s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## Installation
install: ## Installation complète du projet
	@echo "${GREEN}Installation de KTC-Invoice Pro...${RESET}"
	cp -n .env.example .env || true
	$(DOCKER_COMPOSE) down -v || true
	$(DOCKER_COMPOSE) up -d
	@echo "${YELLOW}Attente du démarrage de MySQL...${RESET}"
	@sleep 10
	composer install
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console doctrine:fixtures:load --no-interaction
	npm install
	npm run build
	@echo ""
	@echo "${GREEN}============================================${RESET}"
	@echo "${GREEN}Installation terminée !${RESET}"
	@echo "${GREEN}============================================${RESET}"
	@echo "Application: http://localhost:$${APP_PORT:-8080}"
	@echo "phpMyAdmin:  http://localhost:$${PMA_PORT:-8091}"
	@echo "MailHog:     http://localhost:$${MAILHOG_WEB_PORT:-8026}"
	@echo ""
	@echo "Comptes par défaut:"
	@echo "  admin@ktc-center.com / admin123"
	@echo "  commercial@ktc-center.com / commercial123"
	@echo "${GREEN}============================================${RESET}"

## Docker
start: ## Démarre les conteneurs
	$(DOCKER_COMPOSE) up -d

stop: ## Arrête les conteneurs
	$(DOCKER_COMPOSE) down

restart: ## Redémarre les conteneurs
	$(DOCKER_COMPOSE) restart

build: ## Rebuild les conteneurs
	$(DOCKER_COMPOSE) up -d --build

logs: ## Affiche les logs
	$(DOCKER_COMPOSE) logs -f

logs-app: ## Affiche les logs de l'application
	$(DOCKER_COMPOSE) logs -f app

logs-node: ## Affiche les logs de node (webpack)
	$(DOCKER_COMPOSE) logs -f node

## Shell
shell: ## Accède au shell du conteneur PHP
	$(DOCKER_COMPOSE) exec app bash

shell-node: ## Accède au shell du conteneur Node
	$(DOCKER_COMPOSE) exec node sh

## Base de données
db-init: ## Initialise la base de données (create + migrate + fixtures)
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console doctrine:fixtures:load --no-interaction

db-migrate: ## Exécute les migrations
	php bin/console doctrine:migrations:migrate --no-interaction

db-fixtures: ## Charge les fixtures
	php bin/console doctrine:fixtures:load --no-interaction

db-reset: ## Reset complet de la base de données
	php bin/console doctrine:database:drop --force --if-exists
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console doctrine:fixtures:load --no-interaction

## Cache
cache-clear: ## Vide le cache
	php bin/console cache:clear

## Assets
assets-build: ## Build les assets (production)
	npm run build

assets-watch: ## Watch les assets (développement)
	npm run watch

assets-install: ## Installe les dépendances npm
	npm install

## Tests
test: ## Lance les tests
	php bin/phpunit

test-coverage: ## Lance les tests avec couverture
	php bin/phpunit --coverage-html var/coverage

## Qualité de code
cs-fix: ## Corrige le style du code
	vendor/bin/php-cs-fixer fix

phpstan: ## Analyse statique du code
	vendor/bin/phpstan analyse src --level=5

## Composer
composer-install: ## Installe les dépendances composer
	composer install

composer-update: ## Met à jour les dépendances composer
	composer update

## Commandes Symfony utiles
entity: ## Crée une nouvelle entité
	php bin/console make:entity

controller: ## Crée un nouveau contrôleur
	php bin/console make:controller

form: ## Crée un nouveau formulaire
	php bin/console make:form

migration: ## Crée une nouvelle migration
	php bin/console make:migration