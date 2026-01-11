# Game Scoreboard

A real-time game room leaderboard system built with Laravel 12, Vue 3, and Inertia.js. Track matches, manage ELO ratings, and compete on leaderboards for various games.

## Features

### Core Features
- **ELO Rating System** - Professional-grade rating system for competitive play
- **Multiple Games** - Support for Pool, Backgammon, Darts, Foosball, and more
- **Real-time Updates** - WebSocket-powered live match updates via Laravel Reverb
- **Game Rules** - Display game rules during matches to keep everyone on the same page
- **Quick Match Codes** - 6-character codes for easy match joining
- **Public Leaderboards** - Share your rankings without requiring login
- **Public Profiles** - Unique nicknames with `/@username` profile URLs

### Match Features
- **Team/Group Games** - Support for 1v1, 2v2, 3v3, 4v4, and Free-For-All (FFA) formats
- **Team Switching** - Players can switch teams before match starts (when target team has room)
- **Dynamic Format Changes** - Host can change match format (e.g., 1v1 to 2v2) during lobby
- **Host Indicator** - Crown icon shows match creator/host
- **Invite Players** - Directly invite users to join your match
- **Streak Multiplier** - Rating changes are multiplied when facing opponents on win streaks
  - 3-4 wins: 1.25x multiplier
  - 5-6 wins: 1.5x multiplier
  - 7+ wins: 2x multiplier
- **VS Layout** - Visual side-by-side display with VS badge for matches

### Admin Features
- **Admin Panel** - Manage games, users, and branding settings
- **Install Wizard** - First-run setup for easy deployment
- **Customizable Branding** - Configure app name, logo, and primary color
- **User Management** - View users, toggle admin status
- **Game Management** - Create, edit, enable/disable games
- **Registration Control** - Enable or disable new user registration

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3, TypeScript, Inertia.js
- **Styling**: Tailwind CSS v4
- **State Management**: Pinia
- **Real-time**: Laravel Reverb (WebSockets)
- **Database**: MySQL 8
- **Cache/Sessions**: Redis
- **Data Transfer**: Spatie Laravel Data (auto-generates TypeScript types)
- **Event Sourcing**: Spatie Laravel Event Sourcing

## Requirements

- Docker & Docker Compose
- Git
- Make (optional, but recommended)

## Installation

### Option 1: Quick Setup (Recommended)

```bash
# Clone the repository
git clone https://github.com/rfpdl/Game-Scoreboard.git
cd Game-Scoreboard

# Run setup
make setup
```

This single command will:
- Create `.env` from `.env.example`
- Build and start Docker containers
- Install PHP & Node dependencies
- Generate application key
- Run database migrations
- Seed default games
- Build frontend assets
- Set up storage permissions

Once complete, open **http://localhost:9090**

### Option 2: Manual Installation

If you don't have `make` installed, follow these steps:

```bash
# 1. Clone the repository
git clone https://github.com/rfpdl/Game-Scoreboard.git
cd Game-Scoreboard

# 2. Copy environment file
cp .env.example .env

# 3. Start Docker containers
docker-compose up -d --build

# 4. Wait for MySQL to be ready (about 30 seconds)
docker-compose ps  # All containers should show "Up"

# 5. Install PHP dependencies
docker-compose exec app composer install

# 6. Generate application key
docker-compose exec app php artisan key:generate

# 7. Run database migrations
docker-compose exec app php artisan migrate

# 8. Seed default games
docker-compose exec app php artisan db:seed --class=GameSeeder

# 9. Install Node dependencies and build assets
docker-compose exec app npm install
docker-compose exec app npm run build

# 10. Set storage permissions and create symlink
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app php artisan storage:link
```

Once complete, open **http://localhost:9090**

## First-Time Setup Wizard

When you first access the application, you'll be redirected to the **Install Wizard** where you can:

1. **Set your app name** - Choose a name for your leaderboard
2. **Choose a primary color** - Pick your brand color
3. **Upload a logo** (optional) - Add your own logo image
4. **Create your admin account** - Set up the first admin user

After completing the wizard, you're ready to start creating matches!

## Development

### Start Development Server

```bash
make up
# or
docker-compose up -d
```

- **App**: http://localhost:9090
- **Vite Dev Server**: http://localhost:5173 (hot-reload)

### Useful Commands

```bash
make setup       # Full setup for fresh installation
make up          # Start all containers
make down        # Stop all containers
make logs        # View container logs
make shell       # Open shell in app container
make assets      # Build frontend assets
make migrate     # Run database migrations
make seed        # Seed the database
make fresh       # Fresh migration with seeding
make clear       # Clear all caches
make help        # Show all available commands
```

### TypeScript Types

This project uses **Spatie Laravel Data** with **TypeScript Transformer** to automatically generate TypeScript types from PHP Data classes. This ensures a single source of truth for data structures.

```bash
# Regenerate TypeScript types after changing Data classes
docker-compose exec app php artisan typescript:transform
```

Generated types are output to `resources/types/generated.d.ts` and aliased in `resources/js/types/index.d.ts`.

## Testing

The project includes comprehensive testing at multiple levels.

### PHP Tests (Pest)

```bash
# Run all tests
make test

# Run unit tests only
make test-unit

# Run feature tests only
make test-feature

# Run a specific test file
make test-file file=tests/Feature/MatchFlowTest.php
```

**Test Coverage:**
- `tests/Unit/` - Unit tests (ELO calculator, etc.)
- `tests/Feature/` - Feature tests
  - `InstallControllerTest.php` - Install wizard tests
  - `MatchFlowTest.php` - Complete match lifecycle tests
  - `GameManagementTest.php` - Game CRUD tests
  - `BroadcastEventTest.php` - WebSocket broadcast event tests
  - `Admin/` - Admin panel tests (Settings, Games, Users, Matches)

### Cypress E2E Tests

End-to-end tests using Cypress with Electron browser.

```bash
# Seed test users (required before running e2e tests)
make cy-setup

# Open Cypress interactive runner
make cy-open

# Run Cypress tests headlessly
make cy-run
```

**E2E Test Coverage:**
- `cypress/e2e/auth.cy.ts` - Authentication flows
- `cypress/e2e/navigation.cy.ts` - Navigation and routing
- `cypress/e2e/leaderboard.cy.ts` - Public leaderboard
- `cypress/e2e/match-flow.cy.ts` - Multi-user match flow
- `cypress/e2e/realtime.cy.ts` - Real-time WebSocket features
- `cypress/e2e/admin.cy.ts` - Admin panel
- `cypress/e2e/install.cy.ts` - Install wizard

### Playwright WebSocket Tests

WebSocket and multi-user real-time tests using Playwright (runs in Docker).

```bash
# Run Playwright tests in Docker
make pw-test

# Run Playwright tests locally (requires npm install first)
make pw-test-local
```

**WebSocket Test Coverage:**
- `tests/playwright/websocket.spec.ts`
  - WebSocket connection establishment
  - Multi-user real-time updates (two browser contexts)
  - Match join/start/complete notifications between players
  - WebSocket reconnection after network issues

### Load & Stress Testing (k6)

Performance and stress testing using k6 (runs in Docker).

```bash
# Run HTTP load test
make k6-http

# Run WebSocket stress test
make k6-ws

# Run all stress tests
make stress-test
```

**Stress Test Coverage:**
- `tests/k6/http-load.js` - HTTP endpoint load testing
  - Ramps up to 20 concurrent users
  - Tests login, dashboard, leaderboard, match pages
  - Measures response times and error rates
- `tests/k6/websocket-stress.js` - WebSocket connection stress testing
  - Ramps up to 100 concurrent WebSocket connections
  - Tests connection establishment and message handling
  - Measures connection times and latency

**Test Users (seeded by `make cy-setup`):**
| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | Admin |
| player.a@example.com | password | Player |
| player.b@example.com | password | Player |
| player.c@example.com | password | Player |

### Code Quality

```bash
# Check code style (no changes)
make lint

# Auto-fix code style with Pint
make pint

# Run all checks before commit
make check
```

## Environment Configuration

### Key Settings

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | Game Scoreboard |
| `APP_URL` | Application URL | http://localhost:9090 |
| `APP_LOGO_URL` | URL to logo image | (text logo) |
| `APP_PRIMARY_COLOR` | Primary brand color | #f97316 (orange) |

### Production Settings

For production deployment, update `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=your-domain.com
```

## Default Games

The seeder creates these games with rules:

| Game | Description |
|------|-------------|
| **Pool** | Classic 8-ball pool |
| **Backgammon** | Classic board game |
| **Darts** | 501 darts game |
| **Foosball** | Table football |

Additional games can be added via the Admin Panel at `/admin/games`.

## Main Routes

| Route | Description |
|-------|-------------|
| `/` | Welcome page |
| `/register` | Registration |
| `/login` | Login |
| `/dashboard` | User dashboard |
| `/leaderboard` | Public leaderboards |
| `/matches/create` | Create a new match |
| `/matches/join` | Join a match by code |
| `/@{nickname}` | Public user profile |
| `/admin` | Admin panel (admin only) |

## Troubleshooting

### Container won't start

```bash
docker-compose logs app
docker-compose down -v
docker-compose up -d --build
```

### Database connection error

```bash
# Wait for MySQL to initialize
docker-compose logs mysql
docker-compose exec app php artisan migrate:status
```

### Permission errors

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Assets not loading

```bash
docker-compose exec app npm run build
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan cache:clear
```

### 419 Page Expired (CSRF Error)

```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
# Clear browser cookies and try again
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
