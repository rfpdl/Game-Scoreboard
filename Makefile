.PHONY: up up-tunnel down stop build rebuild logs shell health clean restart open clear optimize status migrate migrate-fresh migrate-rollback seed test test-unit test-feature test-file cy-open cy-run cy-setup pw-test pw-test-local k6-http k6-ws stress-test assets build-assets dev install artisan tinker types fresh setup migration model controller help mysql admin prod-up prod-down prod-build prod-logs prod-shell prod-migrate

# Default target
help:
	@echo "Game Scoreboard - Available Commands"
	@echo ""
	@echo "Development:"
	@echo "  make up              - Start all services (including Vite dev server)"
	@echo "  make up-tunnel       - Start production services (for Cloudflare tunnel)"
	@echo "  make down            - Stop all services"
	@echo "  make restart         - Restart all services"
	@echo "  make dev             - Start app + vite dev server"
	@echo "  make open            - Open app in browser"
	@echo "  make logs            - View container logs"
	@echo "  make shell           - Shell into app container"
	@echo "  make status          - Show container status"
	@echo ""
	@echo "Installation:"
	@echo "  make setup           - Full setup for fresh installation"
	@echo "  make install         - Install all dependencies"
	@echo "  make assets          - Build frontend assets"
	@echo "  make types           - Generate TypeScript types"
	@echo ""
	@echo "Database:"
	@echo "  make migrate         - Run migrations"
	@echo "  make migrate-fresh   - Fresh migration (drop all)"
	@echo "  make migrate-rollback - Rollback last migration"
	@echo "  make seed            - Seed database"
	@echo "  make mysql           - Connect to MySQL CLI"
	@echo ""
	@echo "Testing:"
	@echo "  make test            - Run all PHP tests"
	@echo "  make test-unit       - Run unit tests only"
	@echo "  make test-feature    - Run feature tests only"
	@echo "  make cy-open         - Open Cypress interactive runner"
	@echo "  make cy-run          - Run Cypress tests headlessly"
	@echo "  make cy-setup        - Seed test users for e2e testing"
	@echo "  make pw-test         - Run Playwright WebSocket tests (Docker)"
	@echo "  make pw-test-local   - Run Playwright tests locally"
	@echo "  make k6-http         - Run k6 HTTP load test (Docker)"
	@echo "  make k6-ws           - Run k6 WebSocket stress test (Docker)"
	@echo "  make stress-test     - Run all stress tests"
	@echo ""
	@echo "Production:"
	@echo "  make prod-build      - Build production Docker image"
	@echo "  make prod-up         - Start production services"
	@echo "  make prod-down       - Stop production services"
	@echo "  make prod-logs       - View production logs"
	@echo "  make prod-shell      - Shell into production app container"
	@echo "  make prod-migrate    - Run migrations in production"
	@echo ""
	@echo "Utilities:"
	@echo "  make tinker          - Run Laravel Tinker"
	@echo "  make clear           - Clear all caches"
	@echo "  make optimize        - Optimize for production (cache config, routes, views)"
	@echo "  make artisan cmd=... - Run artisan command"
	@echo "  make fresh           - Full reset (migrate:fresh --seed)"
	@echo "  make admin           - Create/reset admin user"

# =============================================================================
# DEVELOPMENT
# =============================================================================

# Start all services (local development)
up:
	docker-compose up -d
	@echo ""
	@echo "Services started!"
	@echo "  App:  http://localhost:9090"
	@echo "  Vite: http://localhost:5173"
	@echo ""

# Start production services for Cloudflare tunnel (no Vite dev server)
up-tunnel:
	docker-compose up -d app mysql redis reverb
	@echo ""
	@echo "Production services started for Cloudflare tunnel!"
	@echo "  App:     http://localhost:9090"
	@echo "  Reverb:  WebSocket server running"
	@echo ""
	@echo "Note: Run 'make build-assets' first if assets changed"
	@echo ""

# Stop all services
down:
	docker-compose down

# Stop alias
stop: down

# Restart all services
restart:
	docker-compose restart
	@echo "Services restarted!"

# Start app and show Vite logs
dev:
	docker-compose up -d
	@echo "Services started!"
	@echo "  App:  http://localhost:9090"
	@echo "  Vite: http://localhost:5173"
	@echo ""
	@echo "Following Vite logs (Ctrl+C to stop viewing)..."
	docker-compose logs -f node

# View logs
logs:
	docker-compose logs -f

# Shell into Laravel container
shell:
	docker-compose exec app bash

# Show container status
status:
	docker-compose ps

# Open browser
open:
	open http://localhost:9090

# =============================================================================
# INSTALLATION & BUILD
# =============================================================================

# Full setup for fresh installation
setup:
	@echo "Setting up Game Scoreboard..."
	@if [ ! -f .env ]; then cp .env.example .env && echo "Created .env file"; fi
	docker-compose up -d --build
	@echo "Waiting for MySQL to be ready (30 seconds)..."
	@sleep 30
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate --ansi
	docker-compose exec app php artisan migrate
	docker-compose exec app php artisan db:seed --class=GameSeeder
	docker-compose exec app npm install
	docker-compose exec app npm run build
	docker-compose exec app chmod -R 775 storage bootstrap/cache
	docker-compose exec app php artisan storage:link
	@echo ""
	@echo "============================================"
	@echo "  Setup complete!"
	@echo "============================================"
	@echo ""
	@echo "  Open: http://localhost:9090"
	@echo ""
	@echo "  You'll be redirected to the Install Wizard"
	@echo "  to create your admin account."
	@echo ""

# Install all dependencies (for fresh clone)
install:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	composer install --ignore-platform-reqs
	docker-compose build
	docker-compose up -d
	@echo "Waiting for MySQL to be ready..."
	@sleep 10
	docker-compose exec app php artisan key:generate --ansi
	docker-compose exec app php artisan migrate
	docker-compose exec node npm run build
	@echo ""
	@echo "Installation complete!"
	@echo "  App:  http://localhost:9090"
	@echo "  Vite: http://localhost:5173"
	@echo ""
	@echo "Run 'make dev' to start development"

# Build frontend assets for production
assets: build-assets

build-assets:
	docker-compose exec node npm run build

# Generate TypeScript types from Laravel Data classes
types:
	docker-compose exec app php artisan typescript:transform
	@echo "TypeScript types generated at resources/types/generated.d.ts"

# =============================================================================
# DATABASE
# =============================================================================

# Run database migrations
migrate:
	docker-compose exec app php artisan migrate

# Fresh migration (drop all tables and re-run migrations)
migrate-fresh:
	docker-compose exec app php artisan migrate:fresh

# Rollback last migration
migrate-rollback:
	docker-compose exec app php artisan migrate:rollback

# Seed database
seed:
	docker-compose exec app php artisan db:seed

# Full reset - fresh migrations with seed
fresh:
	docker-compose exec app php artisan migrate:fresh --seed
	@echo "Database reset complete!"

# Create or reset admin user
admin:
	docker-compose exec app php artisan admin:create

# Connect to MySQL CLI
mysql:
	docker-compose exec mysql mysql -u gameleaderboard -psecret gameleaderboard

# =============================================================================
# TESTING
# =============================================================================

# Run all tests
test:
	docker-compose exec app php artisan test

# Run unit tests only
test-unit:
	docker-compose exec app php artisan test --testsuite=Unit

# Run feature tests only
test-feature:
	docker-compose exec app php artisan test --testsuite=Feature

# Run specific test file (usage: make test-file file=tests/Unit/EloCalculatorTest.php)
test-file:
	docker-compose exec app php artisan test $(file)

# Open Cypress interactive test runner (uses Electron to avoid Chrome password dialogs)
cy-open:
	npx cypress open --browser electron

# Run Cypress tests headlessly
cy-run:
	npx cypress run --browser electron

# Seed test users for e2e testing
cy-setup:
	docker-compose exec app php artisan db:seed --class=TestUserSeeder

# Run Playwright WebSocket tests in Docker
pw-test:
	docker-compose run --rm playwright sh -c "npm install && npx playwright test"

# Run Playwright tests locally (requires local npm install)
pw-test-local:
	npx playwright test

# Run k6 HTTP load test in Docker
k6-http:
	docker-compose run --rm k6 run /scripts/http-load.js

# Run k6 WebSocket stress test in Docker
k6-ws:
	docker-compose run --rm k6 run /scripts/websocket-stress.js

# Run all stress tests (HTTP + WebSocket)
stress-test: k6-http k6-ws
	@echo "All stress tests completed!"

# =============================================================================
# UTILITIES
# =============================================================================

# Run Laravel artisan command (usage: make artisan cmd="migrate:status")
artisan:
	docker-compose exec app php artisan $(cmd)

# Run Tinker
tinker:
	docker-compose exec app php artisan tinker

# Clear all Laravel caches
clear:
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	@echo "All caches cleared!"

# Optimize for production
optimize:
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	@echo "Application optimized for production!"

# Health check
health:
	@echo "Checking services..."
	@curl -sf http://localhost:9090 > /dev/null && echo "  Laravel: OK" || echo "  Laravel: Not responding"
	@docker-compose exec mysql mysqladmin ping -h localhost -u gameleaderboard -psecret 2>/dev/null && echo "  MySQL: OK" || echo "  MySQL: Not responding"

# =============================================================================
# PRODUCTION
# =============================================================================

# Start production services
prod-up:
	docker compose -f docker-compose.prod.yml up -d
	@echo ""
	@echo "Production services started!"
	@echo ""

# Stop production services
prod-down:
	docker compose -f docker-compose.prod.yml down

# Build production image
prod-build:
	docker compose -f docker-compose.prod.yml build

# View production logs
prod-logs:
	docker compose -f docker-compose.prod.yml logs -f

# Production shell
prod-shell:
	docker compose -f docker-compose.prod.yml exec app sh

# Run migrations in production
prod-migrate:
	docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# =============================================================================
# DOCKER
# =============================================================================

# Build Docker images
build:
	docker-compose build

# Rebuild from scratch (no cache)
rebuild:
	docker-compose down
	docker-compose build --no-cache
	docker-compose up -d

# Clean everything (removes volumes and images)
clean:
	docker-compose down -v --rmi local
	@echo "Cleaned up Docker resources"

# =============================================================================
# CODE QUALITY
# =============================================================================

# Check code style with Pint (no changes)
lint:
	docker-compose exec app ./vendor/bin/pint --test

# Auto-fix code style with Pint
pint:
	docker-compose exec app ./vendor/bin/pint

# Run Rector for code improvements
rector:
	docker-compose exec app ./vendor/bin/rector process

# Auto-fix everything (Rector + Pint)
fix: rector pint

# Run all checks before commit
check: lint test
	@echo "All checks passed!"

# Pre-commit hook target
pre-commit:
	@echo "Running pre-commit checks..."
	@echo "1. Checking code style with Pint..."
	@docker-compose exec app ./vendor/bin/pint --test || (echo "Run 'make fix' to auto-fix code style issues." && exit 1)
	@echo "   Code style OK!"
	@echo ""
	@echo "2. Running unit tests..."
	@docker-compose exec app php artisan test --testsuite=Unit --stop-on-failure || (echo "Tests failed!" && exit 1)
	@echo "   Tests passed!"
	@echo ""
	@echo "All pre-commit checks passed!"

# =============================================================================
# GENERATORS
# =============================================================================

# Create a new migration (usage: make migration name=create_foo_table)
migration:
	docker-compose exec app php artisan make:migration $(name)

# Create a new model (usage: make model name=Foo)
model:
	docker-compose exec app php artisan make:model $(name)

# Create a new controller (usage: make controller name=FooController)
controller:
	docker-compose exec app php artisan make:controller $(name)
