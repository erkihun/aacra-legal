# LDMS Handover Guide

This document is the developer handoff reference for the Legal Department Management System (LDMS). Use it together with the root [README](../README.md) for installation and operations.

## Project Structure

- `app/Actions`
  business transitions and non-trivial query builders
- `app/Console/Commands`
  scheduled reminder commands
- `app/Enums`
  workflow, role, locale, and status enums
- `app/Exports`
  Laravel Excel export classes
- `app/Http/Controllers`
  thin Inertia controllers and small mutation endpoints
- `app/Http/Requests`
  authorization-aware validation
- `app/Http/Resources`
  API/Inertia serialization helpers for complex models
- `app/Jobs`
  queued SMS and Telegram fan-out jobs
- `app/Listeners` and `app/Notifications`
  assignment and reminder notification handling
- `app/Models`
  Eloquent domain models, scopes, relations, and activity-log integration
- `app/Policies`
  resource-level visibility and mutation rules
- `app/Services`
  provider abstractions for SMS and Telegram
  plus deployment-level configuration retrieval in `SystemSettingsService`
- `database/migrations`
  MySQL-friendly schema in dependency order
- `database/seeders`
  roles, reference data, demo users, and sample workflow data
- `resources/js`
  Inertia React pages, layouts, and shared UI components
- `routes/web.php`
  browser routes for dashboards, workflows, reports, notifications, profile, and locale switching
- `routes/console.php`
  scheduler registrations

## Domain Modules

### Deployment Configuration

- Main persistence: `App\Models\SystemSetting`
- Main enum: `App\Enums\SystemSettingGroup`
- Main service: `App\Services\SystemSettingsService`
- Admin controller: `App\Http\Controllers\Admin\SystemSettingsController`
- Update action: `App\Actions\UpdateSystemSettingsGroupAction`

This is not shared multi-tenant SaaS. Each deployment is single-tenant:

- one organization per deployment
- separate server
- separate database
- separate file storage
- separate system settings

### Advisory Workflow

- Main model: `App\Models\AdvisoryRequest`
- Supporting models: `AdvisoryAssignment`, `AdvisoryResponse`, `Attachment`, `Comment`
- Controller: `App\Http\Controllers\AdvisoryRequestController`
- Core actions:
  - `SubmitAdvisoryRequestAction`
  - `DirectorReviewAdvisoryAction`
  - `AssignAdvisoryToExpertAction`
  - `RecordAdvisoryResponseAction`

### Court Case Workflow

- Main model: `App\Models\LegalCase`
- Supporting models: `CaseAssignment`, `CaseHearing`, `Attachment`, `Comment`
- Controller: `App\Http\Controllers\LegalCaseController`
- Core actions:
  - `OpenLegalCaseAction`
  - `DirectorReviewCaseAction`
  - `AssignCaseToExpertAction`
  - `RecordCaseHearingAction`
  - `CloseCaseAction`

### Dashboards and Reporting

- Dashboard query/action: `App\Actions\BuildDashboardDataAction`
- Reports query/action: `App\Actions\BuildReportsDataAction`
- Exports: `app/Exports/*`
- Controllers:
  - `DashboardController`
  - `ReportController`
  - `AuditLogController`
  - `NotificationController`

## Workflow Rules

### Advisory Chain

1. Department requester submits.
2. Legal director reviews.
3. Director forwards to advisory team leader, returns, or rejects.
4. Advisory team leader assigns to a legal expert.
5. Assigned expert records written or verbal advice.
6. Request moves to responded, completed, and closed states through guarded actions.

Guardrails:

- requesters may only submit for their own department
- director assignment must target an active advisory team leader in the advisory team
- team leader assignment must target an active legal expert in the advisory team
- only the assigned expert may respond
- response actions only run from allowed workflow states

### Court Case Chain

1. Registrar opens the case intake.
2. Legal director reviews.
3. Director forwards to litigation team leader, returns, or rejects.
4. Litigation team leader assigns to a legal expert.
5. Expert records hearings and progress.
6. Expert or authorized leadership records outcome and closure through guarded states.

Guardrails:

- director assignment must target an active litigation team leader in the litigation team
- expert assignment must target an active legal expert in the litigation team
- hearing and closure actions are state-guarded
- attachments and comments inherit parent-case authorization

## Role and Permission Model

System roles are defined in `App\Enums\SystemRole`. Role and permission seeding is centralized in `database/seeders/RolesAndPermissionsSeeder.php`.

Primary roles:

- Super Admin
- Legal Director
- Litigation Team Leader
- Advisory Team Leader
- Legal Expert
- Department Requester
- Registrar
- Auditor

Notes:

- The codebase currently carries two permission vocabularies:
  one matching the original business specification such as `legal-cases.view`, and one more capability-oriented set such as `cases.view_any`.
- Controllers and policies are the source of truth for enforcement.
- `HandleInertiaRequests` shares effective role and permission names with the frontend for navigation gating.

## Localization Approach

- Translation files live in `lang/en.json` and `lang/am.json`.
- Locale enum: `App\Enums\LocaleCode`
- Locale middleware: `App\Http\Middleware\SetLocale`
- Locale switching endpoint: `POST /locale`
- Locale is persisted in:
  - session
  - authenticated user profile
- Frontend lookup helper: `resources/js/lib/i18n.ts`

When adding visible UI text:

1. add the key to both JSON files
2. use `useI18n().t('key')` in React pages/components
3. prefer translating backend flash messages and validation-friendly labels too

## Notifications Architecture

- Database and mail-ready notifications live in `app/Notifications`
- Assignment listeners live in `app/Listeners`
- SMS and Telegram fan-out jobs live in `app/Jobs`
- Gateway abstractions live in:
  - `App\Services\Sms\SmsGateway`
  - `App\Services\Telegram\TelegramGateway`

Current gateway behavior:

- `log` driver logs outbound payloads
- `null` driver suppresses delivery
- unsupported configured values fall back to `log` and emit a warning

Where reminders originate:

- `legal:send-upcoming-hearing-reminders`
- `legal:send-overdue-advisory-reminders`
- `legal:send-appeal-deadline-reminders`

## Scheduler and Queue Responsibilities

Scheduler registrations live in `routes/console.php`.

Queue is responsible for:

- mail delivery triggered by notifications
- SMS delivery jobs
- Telegram delivery jobs
- reminder fan-out side effects

Operational expectation:

- at least one queue worker must be running outside local demos
- `schedule:run` must execute every minute in production
- failed jobs should be monitored with `php artisan queue:failed`
- when `CACHE_STORE=database`, run `schedule:list` only after migrations have created the cache tables

## Reporting and Export Architecture

- Report filters are validated by `App\Http\Requests\ReportFilterRequest`
- `BuildReportsDataAction` produces all report datasets from filtered advisory and case queries
- Export classes transform prepared datasets to Excel through Laravel Excel
- Export route:
  `GET /reports/export/{reportType}`

Current report families:

- cases by status
- advisory by department
- expert workload
- turnaround times
- hearing schedule
- overdue items

## Attachment and Security Model

- Attachments are private files stored on the local disk under `storage/app/private`
- Metadata is stored in `App\Models\Attachment`
- Upload action: `App\Actions\StoreAttachmentAction`
- Download controller: `AttachmentController@download`
- Access is enforced through `AttachmentPolicy`, which delegates visibility to the parent attachable resource
- Upload validation:
  - max 5 files per request
  - 10 MB per file
  - limited document and image extensions
  - stored SHA-256 checksum for traceability

Comments and attachments are polymorphic so the same collaboration model works for advisory requests and legal cases.

## Where To Change What

- Change deployment branding, homepage content, default locales, or appearance tokens:
  `app/Services/SystemSettingsService.php`, `app/Http/Requests/Admin/UpdateSystemSettingsRequest.php`, `resources/js/Pages/Admin/SystemSettings/Index.tsx`
- Change how shared branding is exposed to the app:
  `app/Http/Middleware/HandleInertiaRequests.php`, `resources/views/app.blade.php`, `resources/js/app.tsx`, `resources/css/app.css`
- Change public/homepage content rendering:
  `app/Http/Controllers/PublicHomeController.php`, `resources/js/Pages/Public/Home.tsx`, `resources/js/Layouts/PublicLayout.tsx`
- Change auth-shell branding:
  `resources/js/Layouts/GuestLayout.tsx`

- Add or change workflow rules:
  `app/Actions/*`, `app/Policies/*`, `app/Http/Requests/*`
- Change visibility or access rules:
  `app/Policies/*`, `app/Models/*::scopeVisibleTo`
- Add or change dashboard metrics:
  `app/Actions/BuildDashboardDataAction.php`
- Add or change reports or filters:
  `app/Actions/BuildReportsDataAction.php`, `app/Exports/*`, `app/Http/Requests/ReportFilterRequest.php`
- Add or change reminder schedules:
  `routes/console.php`, `app/Console/Commands/*`
- Replace SMS or Telegram providers:
  `app/Services/Sms/*`, `app/Services/Telegram/*`, `AppServiceProvider`
- Change seeded roles or permissions:
  `database/seeders/RolesAndPermissionsSeeder.php`
- Change demo users or sample flows:
  `database/seeders/DemoUserSeeder.php`, `database/seeders/DemoWorkflowSeeder.php`
- Change UI shell or navigation:
  `resources/js/Layouts/AuthenticatedLayout.tsx`
- Change route organization:
  `routes/web.php`
- Add translation keys:
  `lang/en.json`, `lang/am.json`

## Operational Support Notes

- Activity log entries are available through Spatie activity log and the audit timeline UI.
- Authorization denials are logged with request context.
- SMS and Telegram job failures are logged with recipient context.
- Reminder commands continue after individual recipient failures and emit error logs for support follow-up.
