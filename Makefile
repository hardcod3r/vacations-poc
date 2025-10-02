
PHPSTAN_MEMORY ?= 512M
KEY_DIR ?= keys
PRIVATE := $(KEY_DIR)/jwt-private.pem
PUBLIC  := $(KEY_DIR)/jwt-public.pem
OPENSSL ?= openssl

.PHONY: up down restart logs setup migrate seed sh phpstan pest fmt lint e2e

up: keys
	docker compose up -d --build

down: ## Stop and remove containers + volumes
	docker compose down -v

restart: down up ## Restart containers

logs: ## Tail logs
	docker compose logs -f --tail=200

sh: ## Open PHP container shell
	docker compose exec php bash


# Generate once if missing
keys:
	@mkdir -p $(KEY_DIR)
	@if [ ! -f "$(PRIVATE)" ] || [ ! -f "$(PUBLIC)" ]; then \
		$(OPENSSL) genrsa -out "$(PRIVATE)" 2048; \
		$(OPENSSL) rsa -in "$(PRIVATE)" -pubout -out "$(PUBLIC)"; \
		chmod 600 "$(PRIVATE)" "$(PUBLIC)"; \
		echo "Generated JWT keys in $(KEY_DIR)"; \
	else \
		echo "JWT keys already present."; \
	fi

# Force regenerate
regen-keys:
	@mkdir -p $(KEY_DIR)
	@rm -f "$(PRIVATE)" "$(PUBLIC)"
	$(OPENSSL) genrsa -out "$(PRIVATE)" 2048
	$(OPENSSL) rsa -in "$(PRIVATE)" -pubout -out "$(PUBLIC)"
	chmod 600 "$(PRIVATE)" "$(PUBLIC)"
	@echo "Re-generated JWT keys."


setup: ## Install PHP dependencies
	docker compose exec php composer install -n --prefer-dist

migrate: ## Run DB migrations
	docker compose exec php vendor/bin/phinx migrate -e development

seed: ## Run DB seeders
	docker compose exec php vendor/bin/phinx seed:run -e development

e2e: migrate seed ## Run end-to-end preparation (migrate + seed)



phpstan: ## Run static analysis
	docker compose exec php vendor/bin/phpstan analyse --memory-limit=$(PHPSTAN_MEMORY)

pest: ## Run tests
	docker compose exec php vendor/bin/pest -p

fmt: ## Format code
	docker compose exec php composer format

lint: fmt phpstan pest ## Run all checks (format + static analysis + tests)


help: ## Show this help
	@echo "Available make targets:"
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
