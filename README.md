# ClubPilot

ClubPilot is a Laravel application with a static frontend and a Laravel API backend.

## Project structure

- `public/frontend/` contains the browser frontend (`index.html`, `app.js`, and `styles.css`).
- `app/Http/Controllers/Api/` and `routes/api.php` contain the backend API.
- `app/Http/Controllers/Auth/` and `routes/web.php` contain the session-based login and logout.
- `resources/views/auth/` and `resources/views/dashboard.blade.php` contain the login and protected dashboard pages.
- `database/migrations/` contains the database schema.
- `config/database.php` reads the database connection from `.env`. SQLite is the default.

The backend stays outside `public/`; Laravel's `public/` directory is the web root. The login uses Laravel's database-backed users and session, while the dashboard calls `/api/health` to verify the configured database connection.

## Run locally

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Open `http://127.0.0.1:8000` to sign in. After login, the dashboard checks the backend and database connection. The API response is available at `http://127.0.0.1:8000/api/health`. The original static frontend remains available at `/welcome`.

Seed the local demo login with:

```sh
php artisan db:seed
```

Demo credentials: `test@example.com` / `password`. Change or remove this demo account before deploying publicly.

To use another database, set `DB_CONNECTION` and its matching connection values in `.env`, then run `php artisan migrate`.

## Tests

```sh
php artisan test
```
