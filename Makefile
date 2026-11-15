# Motis for Nextcloud - development helpers
# All tooling runs in Docker, no local PHP/Node installation required.
#
# NOTE: The Nextcloud runtime includes vendor/autoload.php through the
# Application class. Composer's dev autoloader remaps OCP\ and OC\ to the
# stub packages, which breaks a running dev instance. Targets that need the
# dev autoloader (test, lint, fix) therefore dump it first and restore the
# runtime autoloader (--no-dev) afterwards.

.DEFAULT_GOAL := help
COMPOSE = docker compose
PHP = docker run --rm --user "$$(id -u):$$(id -g)" -e HOME=/tmp -v "$$(PWD)":/app -w /app lorisleiva/laravel-docker:8.3
NODE = docker run --rm --user "$$(id -u):$$(id -g)" -e HOME=/tmp -v "$$(PWD)":/app -w /app node:24-alpine

.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-12s\033[0m %s\n", $$1, $$2}'

.PHONY: autoload
autoload: ## Restore the runtime (no-dev) composer autoloader
	$(PHP) composer dump-autoload --no-dev

.PHONY: install
install: ## Install composer and npm dependencies
	$(PHP) composer install --no-interaction --no-progress
	@# Keep the runtime autoloader free of dev namespace remappings (OCP/OC stubs)
	$(PHP) composer dump-autoload --no-dev
	$(NODE) npm ci

.PHONY: build
build: ## Build the frontend (production mode)
	$(NODE) npm run build

.PHONY: dev
dev: ## Start the local Nextcloud dev instance and enable the app
	$(COMPOSE) up -d
	@echo "Waiting for Nextcloud to finish installation..."
	@until $(COMPOSE) exec -T nextcloud php occ status 2>/dev/null | grep -q "installed: true"; do sleep 5; done
	$(COMPOSE) exec -u www-data nextcloud php occ app:enable motis
	@echo "Done: http://localhost:8090 (admin / admin)"

.PHONY: stop
stop: ## Stop the local Nextcloud dev instance
	$(COMPOSE) down

.PHONY: test
test: ## Run PHP unit tests with coverage (restores runtime autoloader afterwards)
	$(PHP) sh -c 'composer dump-autoload >/dev/null && php -d xdebug.mode=coverage vendor/bin/phpunit -c tests/phpunit.unit.xml --coverage-text; status=$$?; composer dump-autoload --no-dev >/dev/null; exit $$status'

.PHONY: lint
lint: ## Run all linters (PHP + JS/SCSS)
	$(PHP) sh -c 'composer dump-autoload >/dev/null && composer lint && composer cs:check; status=$$?; composer dump-autoload --no-dev >/dev/null; exit $$status'
	$(NODE) npm run lint
	$(NODE) npm run stylelint

.PHONY: fix
fix: ## Auto-fix lint issues
	$(PHP) sh -c 'composer dump-autoload >/dev/null && composer cs:fix; status=$$?; composer dump-autoload --no-dev >/dev/null; exit $$status'
	$(NODE) npm run lint:fix
	$(NODE) npm run stylelint:fix
