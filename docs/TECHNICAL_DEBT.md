# LDMS Technical Debt Register

This register captures known non-blocking debt at the time of handover.

## Blockers

- None currently identified for developer handoff or internal testing.

## Medium Priority Improvements

- Replace the SMS and Telegram `log` or `null` gateway stubs with real provider adapters and delivery status tracking.
- Split `routes/web.php` into domain route files if the application grows further; the current single file is still manageable but already spans all browser routes.
- Reduce duplicated permission vocabulary over time. The codebase currently supports both legacy permission names and newer capability-style names for compatibility.
- Add document malware scanning and stronger file-content validation beyond MIME and extension checks.
- Review `HandleInertiaRequests` shared permission payload size if the role or permission matrix grows significantly.
- Consider queue tagging, structured monitoring, or Horizon if reminder volume and notification load increase.
- Add stronger pagination and server-side filtering tests for larger reporting datasets.

## Low Priority Cleanup

- Introduce stronger frontend TypeScript interfaces for complex page props instead of a few remaining `any` shapes in workflow pages.
- Extract route constants or route-specific docs if frontend navigation becomes broader.
- Add more focused architecture docs per module if the team expands and multiple developers work on the same bounded context concurrently.
- Revisit seeded demo phone numbers and contact metadata before using seeded data outside local or QA environments.
