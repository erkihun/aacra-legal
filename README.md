# Legal Department Management System

Legal Department Management System (LDMS) is a Laravel 12 application for institutional legal operations. It supports hierarchical legal advisory requests, court case follow-up, secure document handling, auditability, bilingual interfaces, role-based access control, reminders, and operational reporting.

Additional handover documentation:

- [docs/HANDOVER.md](docs/HANDOVER.md)
- [docs/TECHNICAL_DEBT.md](docs/TECHNICAL_DEBT.md)

## Stack

- PHP 8.3+ target runtime
- Laravel 12
- Inertia.js 2
- React 18 + TypeScript
- Tailwind CSS
- MySQL-friendly schema and seeders
- Laravel queues, notifications, scheduler, and policies
- `spatie/laravel-permission`
- `spatie/laravel-activitylog`
- `maatwebsite/excel`

## Core Capabilities

- Authentication, password reset, profile management, and locale preference
- Single-tenant deployment branding and organization profile management
- Legal advisory workflow:
  department requester -> legal director -> advisory team leader -> legal expert
- Court case workflow:
  registrar -> legal director -> litigation team leader -> legal expert
- Secure private attachments with policy-protected downloads
- Internal comments and activity timeline visibility
- Role-aware dashboards and exportable reports
- Database notifications plus mail-ready, SMS, and Telegram delivery hooks
- Scheduled reminders for hearings, overdue advisory requests, and appeal deadlines
- English and Amharic localization

## Prerequisites

- PHP 8.3 or newer
- Composer
- Node.js 20+ and npm
- MySQL 8+ for production
- A queue worker process for async notifications and reminder fan-out

The project was implementation-tested in this workspace with PHP 8.2. Release deployment should use PHP 8.3+.

## Installation

1. Install dependencies.

```bash
composer install
npm install
```

2. Create the environment file and application key.

```bash
cp .env.example .env
php artisan key:generate
```

3. Create the SQLite file for quick local evaluation, or switch to MySQL before migrating.

```bash
mkdir -p database
touch database/database.sqlite
```

4. Run migrations and seeders.

```bash
php artisan migrate --seed
```

5. Start the development processes.

```bash
composer run dev
```

`composer run dev` starts the Laravel server, queue listener, and Vite. It intentionally does not run `php artisan pail` so the command works cleanly on Windows as well.

## Environment Setup

Key environment values used by the application:

```env
APP_NAME="Legal Department Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Africa/Addis_Ababa

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ldms
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"

SMS_DRIVER=log
SMS_BASE_URL=
SMS_TOKEN=
SMS_SENDER=LDMS

TELEGRAM_DRIVER=log
TELEGRAM_BOT_TOKEN=
```

### Environment Notes

- `FILESYSTEM_DISK=local` stores attachments on the private local disk under `storage/app/private`.
- Attachment downloads are not public URLs; they are streamed through the application after policy checks.
- `SMS_DRIVER` and `TELEGRAM_DRIVER` currently support `log` and `null`.
- `CACHE_STORE=database`, `SESSION_DRIVER=database`, and `QUEUE_CONNECTION=database` are the intended default operational drivers for a single-node deployment.
- For HTTPS deployments, set `SESSION_SECURE_COOKIE=true`.

## Migrate and Seed

Fresh install:

```bash
php artisan migrate:fresh --seed
```

The seeding flow creates:

- roles and permissions
- departments
- teams
- advisory categories
- courts
- legal case types
- demo users
- one sample advisory and one sample case workflow

## Default Demo Users

All seeded demo users use password `password`.

- `admin@ldms.test` -> Super Admin
- `director@ldms.test` -> Legal Director
- `litigation.lead@ldms.test` -> Litigation Team Leader
- `advisory.lead@ldms.test` -> Advisory Team Leader
- `expert.one@ldms.test` -> Legal Expert
- `expert.two@ldms.test` -> Legal Expert
- `requester@ldms.test` -> Department Requester
- `registrar@ldms.test` -> Registrar
- `auditor@ldms.test` -> Auditor

## Running Locally

Single command:

```bash
composer run dev
```

Manual processes:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Optional log tailing:

```bash
php artisan pail
```

`php artisan pail` may not work on Windows if `pcntl` is unavailable.

## Queue Worker

Notifications, SMS fan-out, Telegram fan-out, and reminder side effects rely on the queue.

Development:

```bash
php artisan queue:work
```

Production example:

```bash
php artisan queue:work --queue=default --tries=3 --backoff=60
```

Monitor failed jobs:

```bash
php artisan queue:failed
php artisan queue:retry all
```

## Scheduler

Registered scheduled commands:

- `legal:send-upcoming-hearing-reminders` at `08:00` on weekdays
- `legal:send-overdue-advisory-reminders` at `08:30` on weekdays
- `legal:send-appeal-deadline-reminders` at `09:00` on weekdays

Production cron entry:

```bash
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

When `CACHE_STORE=database`, run scheduler inspection commands such as `php artisan schedule:list` after migrations so the cache and lock tables already exist.

## Testing

Backend suite:

```bash
php artisan test
```

Fresh install verification:

```bash
php artisan migrate:fresh --seed
```

Formatting:

```bash
vendor/bin/pint --test
```

Frontend production build:

```bash
npm run build
```

## Build Assets

```bash
npm run build
```

Compiled assets are emitted to `public/build`.

## Localization

- Supported locales: English (`en`) and Amharic (`am`)
- Locale is persisted both in session and on the authenticated user profile
- The locale middleware applies the saved preference on authenticated requests

## Single-Tenant Deployment Configuration

Each deployment of LDMS is intended for exactly one organization:

- one server
- one database
- one file store
- one independent settings dataset

The same codebase can be deployed separately for multiple organizations, but deployments do not share runtime data.

### Managed In The Admin UI

System Settings is the deployment configuration layer for:

- organization identity and legal department name
- deployment tagline and footer text
- logos and favicon
- primary, secondary, and accent colors
- button and card style preferences
- supported locales and default locale
- public website homepage content and hero slides
- notification toggles and reminder lead times
- upload constraints and selected appearance defaults

### Managed In `.env` Or Server Config

Keep infrastructure and secret values out of the normal admin UI:

- database credentials
- queue driver and worker process settings
- app key
- mail credentials
- storage driver credentials
- real SMS provider secrets
- real Telegram bot secrets
- TLS, proxy, and host configuration

## Notification Integrations

Currently implemented:

- database notifications
- queued mail notifications
- SMS abstraction via `App\Services\Sms\SmsGateway`
- Telegram abstraction via `App\Services\Telegram\TelegramGateway`

Release candidate behavior:

- `log` driver writes outbound SMS or Telegram payloads to the application log
- `null` driver suppresses delivery without changing business flow
- unsupported custom driver values fall back to `log` and emit a warning

## Reporting

Available reports and exports:

- cases by status
- advisory requests by department
- expert workload
- turnaround times
- hearing schedule
- overdue items

Exports are generated as `.xlsx` files through Laravel Excel.

## Deployment Notes

Recommended production flow:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Operational requirements:

- run at least one persistent queue worker
- configure the Laravel scheduler through cron or a supervisor-managed process
- back up the database and `storage/app/private` attachments directory
- back up `storage/app/public/branding` for deployment branding assets
- keep `APP_DEBUG=false` in production
- use HTTPS and `SESSION_SECURE_COOKIE=true`
- set a real `MAIL_MAILER` before enabling mail delivery outside local environments

For a new organization deployment:

1. deploy the same codebase to a separate server
2. point it to a separate database
3. run migrations and seeders
4. sign in as `Super Admin`
5. configure branding, organization profile, locales, and public website content under `System Settings`

## Observability Notes

- Activity trails are recorded through Spatie activity log.
- Authorization denials are reported with request context.
- SMS and Telegram delivery jobs log failures with recipient context.
- Reminder commands log per-recipient delivery failures and continue processing remaining recipients.

## Architecture Notes

- Controllers are thin and delegate business transitions to action classes in `app/Actions`.
- Policies and permissions control visibility and workflow mutations.
- Workflow state is modeled with backed enums in `app/Enums`.
- UUID primary keys are generated through `app/Concerns/HasUuidPrimaryKey.php`.
- Private attachments are stored with metadata, SHA-256 hashes, and uploader references.
