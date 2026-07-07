.PHONY: help install dev test seed migrate deploy build up down logs shell

# Laravel API Makefile
# Standalone commands for Laravel API service

YELLOW := \033[0;33m
GREEN := \033[0;32m
RED := \033[0;31m
BLUE := \033[0;34m
NC := \033[0m

# ============================================================================
# CONFIGURATION
# ============================================================================

SERVICE_NAME := laravel-api
DOCKER_IMAGE := $(SERVICE_NAME):latest
CONTAINER_NAME := anime-laravel

help: ## Show all available commands
	@echo "$(BLUE)=== Laravel API Makefile ===$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(YELLOW)%-25s$(NC) %s\n", $$1, $$2}'

# ============================================================================
# INSTALLATION & SETUP
# ============================================================================

install: ## Full setup: install deps, build, migrate, seed
	@echo "$(GREEN)Installing Laravel API...$(NC)"
	composer install
	cp .env.example .env
	php artisan key:generate
	@echo "$(GREEN)✓ Installation complete$(NC)"

deps: ## Install Composer dependencies
	@echo "$(GREEN)Installing Composer dependencies...$(NC)"
	composer install
	@echo "$(GREEN)✓ Dependencies installed$(NC)"

deps-update: ## Update Composer dependencies
	@echo "$(YELLOW)Updating dependencies...$(NC)"
	composer update
	@echo "$(GREEN)✓ Dependencies updated$(NC)"

# ============================================================================
# DOCKER OPERATIONS
# ============================================================================

build: ## Build Docker image
	@echo "$(BLUE)Building Docker image: $(DOCKER_IMAGE)...$(NC)"
	docker build -t $(DOCKER_IMAGE) .
	@echo "$(GREEN)✓ Image built$(NC)"

up: ## Start Laravel service
	@echo "$(GREEN)Starting $(SERVICE_NAME)...$(NC)"
	docker run -d \
		--name $(CONTAINER_NAME) \
		-p 8000:8000 \
		-v $(PWD):/app \
		-w /app \
		$(DOCKER_IMAGE) \
		php artisan serve --host=0.0.0.0 --port=8000
	@echo "$(GREEN)✓ Service running at http://localhost:8000$(NC)"

down: ## Stop Laravel service
	@echo "$(YELLOW)Stopping $(SERVICE_NAME)...$(NC)"
	docker stop $(CONTAINER_NAME) 2>/dev/null || true
	docker rm $(CONTAINER_NAME) 2>/dev/null || true
	@echo "$(GREEN)✓ Service stopped$(NC)"

restart: ## Restart Laravel service
	@echo "$(YELLOW)Restarting $(SERVICE_NAME)...$(NC)"
	@make down
	@make up

logs: ## Tail service logs
	docker logs -f $(CONTAINER_NAME)

ps: ## Show running containers
	docker ps | grep $(SERVICE_NAME) || echo "$(YELLOW)No running containers$(NC)"

# ============================================================================
# DATABASE & MIGRATIONS
# ============================================================================

migrate: ## Run migrations
	@echo "$(GREEN)Running migrations...$(NC)"
	php artisan migrate

migrate-fresh: ## Fresh migrations (⚠️ drops all data)
	@echo "$(RED)⚠️  This will DROP all data!$(NC)"
	php artisan migrate:fresh --force

migrate-rollback: ## Rollback last migration
	@echo "$(YELLOW)Rolling back migrations...$(NC)"
	php artisan migrate:rollback

seed: ## Seed database with sample data
	@echo "$(GREEN)Seeding database...$(NC)"
	php artisan db:seed

seed-fresh: ## Fresh migrations + seed
	@echo "$(GREEN)Fresh database with seeds...$(NC)"
	php artisan migrate:fresh --seed --force

# ============================================================================
# LARAVEL COMMANDS
# ============================================================================

tinker: ## Open Laravel Tinker shell
	php artisan tinker

serve: ## Start local development server
	@echo "$(GREEN)Starting development server...$(NC)"
	php artisan serve --host=0.0.0.0 --port=8000

serve-prod: ## Start server in production mode
	@echo "$(GREEN)Starting production server...$(NC)"
	php artisan serve --host=0.0.0.0 --port=8000 --env=production

queue-work: ## Start queue worker
	@echo "$(GREEN)Starting queue worker...$(NC)"
	php artisan queue:work

queue-failed: ## List failed queue jobs
	php artisan queue:failed

queue-retry: ## Retry failed jobs
	php artisan queue:retry all

cache-clear: ## Clear application cache
	@echo "$(YELLOW)Clearing cache...$(NC)"
	php artisan cache:clear
	@echo "$(GREEN)✓ Cache cleared$(NC)"

config-cache: ## Cache configuration
	@echo "$(BLUE)Caching configuration...$(NC)"
	php artisan config:cache

config-clear: ## Clear config cache
	php artisan config:clear

route-cache: ## Cache routes
	php artisan route:cache

route-clear: ## Clear route cache
	php artisan route:clear

view-cache: ## Cache views
	php artisan view:cache

view-clear: ## Clear view cache
	php artisan view:clear

optimize: ## Optimize application (config + routes + views)
	@echo "$(BLUE)Optimizing application...$(NC)"
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache
	@echo "$(GREEN)✓ Optimization complete$(NC)"

clear-all: ## Clear all caches
	@echo "$(YELLOW)Clearing all caches...$(NC)"
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	@echo "$(GREEN)✓ All caches cleared$(NC)"

# ============================================================================
# TESTING
# ============================================================================

test: ## Run all tests
	@echo "$(BLUE)Running tests...$(NC)"
	php artisan test

test-unit: ## Run unit tests only
	php artisan test --testsuite=Unit

test-feature: ## Run feature tests only
	php artisan test --testsuite=Feature

test-watch: ## Run tests in watch mode
	php artisan test --watch

test-coverage: ## Generate test coverage report
	@echo "$(BLUE)Generating coverage report...$(NC)"
	php artisan test --coverage
	@echo "$(GREEN)✓ Report in storage/coverage/index.html$(NC)"

test-parallel: ## Run tests in parallel
	php artisan test --parallel

# ============================================================================
# CODE QUALITY
# ============================================================================

lint: ## Lint PHP code (PSR12)
	@echo "$(BLUE)Linting code...$(NC)"
	./vendor/bin/phpcs app/ --standard=PSR12

lint-fix: ## Auto-fix code style
	@echo "$(BLUE)Fixing code style...$(NC)"
	./vendor/bin/phpcbf app/ --standard=PSR12

phpstan: ## Static analysis with PHPStan
	@echo "$(BLUE)Running PHPStan...$(NC)"
	./vendor/bin/phpstan analyse app/

format: ## Format code with Pint
	@echo "$(BLUE)Formatting code...$(NC)"
	./vendor/bin/pint

audit: ## Audit dependencies for vulnerabilities
	@echo "$(BLUE)Auditing Composer packages...$(NC)"
	composer audit

validate: ## Validate composer.json
	composer validate

# ============================================================================
# DEVELOPMENT
# ============================================================================

dev: serve ## Start development server

watch-dev: ## Watch code changes and restart
	@echo "$(BLUE)Watching for changes...$(NC)"
	./vendor/bin/composer-require-checker
	@while true; do \
		inotifywait -r -e modify app routes; \
		clear; \
		make lint-fix; \
	done

stub: ## Generate stub files for IDE autocomplete
	php artisan ide-helper:generate
	php artisan ide-helper:models
	php artisan ide-helper:meta

tinker-live: ## Open Tinker in interactive mode
	@echo "$(BLUE)Opening Tinker shell...$(NC)"
	php artisan tinker

# ============================================================================
# DATABASE UTILITIES
# ============================================================================

fresh-db: migrate-fresh seed ## Reset database completely
	@echo "$(GREEN)✓ Database reset$(NC)"

backup-db: ## Backup database
	@mkdir -p backups
	@echo "$(GREEN)Backing up database...$(NC)"
	mysqldump -u $(DB_USERNAME) -p$(DB_PASSWORD) $(DB_DATABASE) > backups/db_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✓ Backup complete$(NC)"

restore-db: ## Restore database (usage: make restore-db FILE=backups/db_*.sql)
	@echo "$(YELLOW)Restoring from $(FILE)...$(NC)"
	mysql -u $(DB_USERNAME) -p$(DB_PASSWORD) $(DB_DATABASE) < $(FILE)
	@echo "$(GREEN)✓ Restore complete$(NC)"

# ============================================================================
# UTILITIES
# ============================================================================

artisan: ## Run artisan command (usage: make artisan CMD="migrate")
	php artisan $(CMD)

composer: ## Run composer command (usage: make composer CMD="require vendor/package")
	composer $(CMD)

install-pkg: ## Install package (usage: make install-pkg PKG="vendor/package")
	composer require $(PKG)

remove-pkg: ## Remove package (usage: make remove-pkg PKG="vendor/package")
	composer remove $(PKG)

version: ## Show Laravel version
	php artisan --version

info: ## Show environment info
	@echo "$(BLUE)=== Laravel API Info ===$(NC)"
	php artisan about
	@echo ""
	@echo "$(BLUE)Dependencies:$(NC)"
	@composer show --latest 2>/dev/null | head -10

env-check: ## Check .env configuration
	@echo "$(BLUE)=== Environment Check ===$(NC)"
	@test -f .env && echo "$(GREEN)✓ .env exists$(NC)" || echo "$(RED)✗ .env missing$(NC)"
	@grep -q "APP_KEY=" .env && echo "$(GREEN)✓ APP_KEY set$(NC)" || echo "$(RED)✗ APP_KEY missing$(NC)"
	@grep -q "DB_" .env && echo "$(GREEN)✓ Database config set$(NC)" || echo "$(RED)✗ Database config missing$(NC)"

# ============================================================================
# CLEANUP
# ============================================================================

clean: ## Clean temporary files
	@echo "$(YELLOW)Cleaning up...$(NC)"
	rm -rf storage/logs/*
	rm -rf storage/cache/*
	rm -rf bootstrap/cache/*
	@echo "$(GREEN)✓ Cleaned$(NC)"

clean-vendor: ## Remove vendor directory
	@echo "$(YELLOW)Removing vendor...$(NC)"
	rm -rf vendor/
	@echo "$(GREEN)✓ Vendor removed$(NC)"

clean-all: clean clean-vendor ## Complete cleanup
	@echo "$(GREEN)✓ Complete cleanup done$(NC)"

prune: ## Remove dangling Docker images
	docker image prune -f

# ============================================================================
# DOCKER COMPOSE INTEGRATION
# ============================================================================

docker-dev: build ## Build and run with Docker
	@echo "$(GREEN)Starting with Docker...$(NC)"
	docker-compose up -d laravel
	@echo "$(GREEN)✓ Running at http://localhost:8000$(NC)"

docker-down: ## Stop Docker service
	docker-compose down

docker-logs: ## View Docker logs
	docker-compose logs -f laravel

docker-shell: ## Access Docker container shell
	docker-compose exec laravel bash

docker-migrate: ## Run migrations in Docker
	docker-compose exec -T laravel php artisan migrate --force

docker-seed: ## Seed database in Docker
	docker-compose exec -T laravel php artisan db:seed

# ============================================================================
# PRODUCTION
# ============================================================================

prod-build: ## Build production image
	docker build -t $(DOCKER_IMAGE)-prod --build-arg APP_ENV=production .

prod-deploy: prod-build ## Deploy production image
	@echo "$(GREEN)Production build ready$(NC)"
	@echo "$(YELLOW)Push to registry and deploy:$(NC)"
	@echo "  docker push $(DOCKER_IMAGE)-prod"

prod-optimize: ## Optimize for production
	@echo "$(BLUE)Optimizing for production...$(NC)"
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache
	composer install --no-dev --optimize-autoloader
	@echo "$(GREEN)✓ Ready for production$(NC)"

.DEFAULT_GOAL := help
