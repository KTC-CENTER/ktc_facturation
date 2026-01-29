# KTC-Invoice Pro - Makefile
# ===========================

.PHONY: help install start stop restart build logs shell db-migrate db-fixtures cache-clear test

# Variables
DOCKER_COMPOSE = docker compose
PHP_CONTAINER = ktc_app
NODE_CONTAINER = ktc_node

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
	$(DOCKER_COMPOSE) up -d --build
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) composer install
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:database:create --if-not-exists
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:fixtures:load --no-interaction
	@echo "${GREEN}Installation terminée !${RESET}"
	@echo "Application: http://localhost:8080"
	@echo "phpMyAdmin:  http://localhost:8081"
	@echo "MailHog:     http://localhost:8025"

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
	$(DOCKER_COMPOSE) logs -f $(PHP_CONTAINER)

logs-node: ## Affiche les logs de node (webpack)
	$(DOCKER_COMPOSE) logs -f $(NODE_CONTAINER)

## Shell
shell: ## Accède au shell du conteneur PHP
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) bash

shell-node: ## Accède au shell du conteneur Node
	$(DOCKER_COMPOSE) exec $(NODE_CONTAINER) sh

## Base de données
db-migrate: ## Exécute les migrations
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

db-fixtures: ## Charge les fixtures
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:fixtures:load --no-interaction

db-reset: ## Reset la base de données
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:database:drop --force --if-exists
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:database:create
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:fixtures:load --no-interaction

## Cache
cache-clear: ## Vide le cache
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console cache:clear

## Assets
assets-build: ## Build les assets (production)
	$(DOCKER_COMPOSE) exec $(NODE_CONTAINER) npm run build

assets-watch: ## Watch les assets (développement)
	$(DOCKER_COMPOSE) exec $(NODE_CONTAINER) npm run watch

assets-install: ## Installe les dépendances npm
	$(DOCKER_COMPOSE) exec $(NODE_CONTAINER) npm install

## Tests
test: ## Lance les tests
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/phpunit

test-coverage: ## Lance les tests avec couverture
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/phpunit --coverage-html var/coverage

## Qualité de code
cs-fix: ## Corrige le style du code
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/php-cs-fixer fix

phpstan: ## Analyse statique du code
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/phpstan analyse src --level=5

## Composer
composer-install: ## Installe les dépendances composer
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) composer install

composer-update: ## Met à jour les dépendances composer
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) composer update

## Commandes utiles
entity: ## Crée une nouvelle entité
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console make:entity

controller: ## Crée un nouveau contrôleur
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console make:controller

form: ## Crée un nouveau formulaire
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console make:form

migration: ## Crée une nouvelle migration
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console make:migration
