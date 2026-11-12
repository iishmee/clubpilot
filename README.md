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

Seed the basic local demo member with:

```sh
php artisan db:seed
```

The demo member cannot access organizer or administrator pages. Do not use demo accounts in a public deployment.

To use another database, set `DB_CONNECTION` and its matching connection values in `.env`, then run `php artisan migrate`.

## Event proposals and venue reservations

The application supports organizer-owned event proposals with the statuses Draft, Pending Review, Revision Requested, Approved, and Rejected. An organizer saves a draft from **Event proposals**, then submits it for administrator review. Administrators can approve it, request revisions, or reject it; revision and rejection decisions require feedback. Organizers can edit draft or revision-requested proposals and see the review history and latest feedback on the event page.

Administrators can add venues from **Venues**. Organizers may request a venue only for an approved event. Pending and approved reservations block overlapping requests for the same venue; adjacent time intervals are allowed. Administrators review pending venue requests and must provide a reason when rejecting one.

### Assigning application roles

New users default to `member`; members cannot access organizer or administrator pages and will receive 403 responses if they navigate to those routes. Set `users.role` to `organizer` for club event organizers or `admin` for university administrators using your normal trusted account-provisioning process. For example, with `php artisan tinker`, an administrator can be assigned with `App\Models\User::where('email', 'admin@your-university.edu')->update(['role' => 'admin']);`. Replace the address with your own account; do not use a public demo credential for an administrator. Roles are not self-service or selectable from the login form.

The starter project does not yet have functional club membership data, so organizer authority is account-wide and proposals store the club name as entered; organizers can still only manage proposals they created. Add club membership authorization when that part of the application is implemented.

After assigning roles and running migrations, sign in as an administrator to add the venue directory and review submitted work. Organizers can then create and submit proposals, and reserve venues after approval. Keep MySQL as the production database to get per-venue row locking during concurrent reservation requests; the test suite uses SQLite.

### Local organizer and administrator test accounts

The 403 response is expected for a `member`: event creation/list access requires the `organizer` role, while review queues and venue management require `admin`. Create two separate local test accounts with interactively entered names, emails, and hidden password prompts:

```sh
php artisan migrate
php artisan clubpilot:setup-local-test-accounts
```

The command runs only when `APP_ENV=local` or `APP_ENV=testing`. Choose two different email addresses and enter a unique password of at least 12 characters for each account when prompted. Password input is hidden and passwords are never printed. If an account with the chosen email already exists with the required role, it is left unchanged; an account with any other role is refused and never promoted. To sign in, use each email and the password you entered into the existing login form; no account password is stored in this README. Do not use this local command to provision production administrators.

Start the application with `php artisan serve` and open:

- Dashboard/navigation: `http://127.0.0.1:8000/dashboard`
- Event proposal list and creation: `http://127.0.0.1:8000/events` and `http://127.0.0.1:8000/events/create`
- Administrator proposal review queue: `http://127.0.0.1:8000/event-reviews`
- Venue directory and administrator venue creation: `http://127.0.0.1:8000/venues` and `http://127.0.0.1:8000/venues/create`
- Reservation list: `http://127.0.0.1:8000/reservations`
- Administrator reservation review queue: `http://127.0.0.1:8000/venue-request-reviews`

To exercise the full workflow, sign in as the organizer, create an event proposal, then submit it. Sign out and sign in as the administrator; open **Proposal review**, review the event, and approve it. Add a venue under **Venues** if the directory is empty. Sign back in as the organizer, open the approved proposal, choose **Request a venue**, and submit a non-conflicting time. Finally, sign in as the administrator, open **Reservation review**, and approve the request. The organizer sees the approved reservation and venue details on the event page. Revision/rejection decisions require feedback; rejecting a reservation requires a reason.

The default test suite uses SQLite and verifies overlap handling, but SQLite cannot verify MySQL row-lock concurrency. An opt-in integration test exercises two concurrent transactions against a dedicated MySQL test database. Set `MYSQL_CONCURRENCY_TEST=1`, configure `DB_CONNECTION=mysql` and a dedicated `DB_DATABASE` ending in `_test`, ensure PHP's `pcntl` extension is installed, and run:

```sh
php artisan test --filter=MySqlVenueReservationConcurrencyTest
```

The test refuses databases not ending in `_test` and migrates that test database. It is skipped unless all MySQL integration prerequisites are met.

## Tests

```sh
php artisan test
```
