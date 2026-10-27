# Laravel + Timeliners IDP Starter

A minimal Laravel starter template with **Vue 3 + Inertia** and login via the **Timeliners identity provider** (OpenID Connect). There are no local passwords, registration or two-factor flows. Users sign in through Timeliners and are created or updated locally by their `sub` claim.

## Stack

- PHP 8.3+, Laravel 13
- Inertia 3, Vue 3, Tailwind CSS 4, Vite
- Laravel Socialite with the OpenID Connect provider
- PostgreSQL
- Pest, Larastan, Pint

## Recommended setup

For the best experience, use:

- [**YERD**](https://yerd.app/) to serve the app locally
- A **PostgreSQL** database with all features available
- `npm run dev` for live UI rendering on every change

## Getting started

```bash
composer setup          # installs dependencies, creates .env, generates key, migrates, builds assets
```

Or manually:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Configure the database and the Timeliners connection in `.env` (see below), then:

```bash
php artisan migrate
npm run dev
```

Serve the app with YERD and open the site in your browser. Every change to the UI is rendered live.

## Configuration

### Database

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### Timeliners IDP

```dotenv
TIMELINERS_BASE_URL=https://identity.timeliners.org
TIMELINERS_CLIENT_ID=your-client-id
TIMELINERS_CLIENT_SECRET=your-client-secret
TIMELINERS_REDIRECT_URI=https://your-app.test/auth/callback
```

Register the redirect URI with Timeliners exactly as set above. The connection is defined in [config/oidc.php](config/oidc.php) and registered as the Socialite driver `oidc_timeliners`.

## How authentication works

Routes live in [routes/auth.php](routes/auth.php):

| Route                | Purpose                                                                  |
| -------------------- | ------------------------------------------------------------------------ |
| `GET /login`         | Redirects to the Timeliners IDP                                          |
| `GET /auth/callback` | Receives the IDP response, creates or updates the user by `sub`, logs in |
| `POST /logout`       | Logs out and invalidates the session                                     |

The `users` table stores `sub`, `name` and `email`. Protect your own routes with the `auth` middleware, as done for `/dashboard` in [routes/web.php](routes/web.php).

## Common commands

```bash
npm run dev             # Vite dev server with live UI updates
npm run build           # Production build
npm run check           # Frontend lint/format checks
composer test           # Pint, Larastan and the test suite
composer lint           # Fix code style with Pint
```

## Docker

A PHP 8.5 image is available in [docker/php85](docker/php85). Publishing a GitHub release runs the tests and builds the container image.
