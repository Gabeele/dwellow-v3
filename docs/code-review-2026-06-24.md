# Whole-codebase review — 2026-06-24

Branch: `code-review-refactor` (off `main`). Scope: full Laravel backend (controllers, requests,
resources, models, observers, policies, screening, mail/notifications/listeners, Filament, providers,
migrations, enums, concerns).

Overall the codebase is in strong shape: consistent return types, FormRequests everywhere, policy-gated
controllers, enum casts, immutable form snapshots, honeypot + timing spam guard, redacted security emails,
DB-level cascades and unique constraints. Findings below are targeted; each is fixed on its own commit
with a covering test.

## High

- **H1 — Applicant document files are orphaned (and never deleted) on cascade deletion.**
  `ApplicationController::destroy` deletes each document's file from the private disk, but deleting a
  `Property`, `Unit`, or `ApplicationLink` cascades at the DB level (`cascadeOnDelete`) down to
  `documents` rows **without** firing model events, so the physical files (government IDs, pay stubs,
  self-reported credit reports) linger on disk forever. This is both a storage leak and a privacy
  problem — deleted applicants' sensitive files are never purged.
  *Fix:* centralize file purging so it runs on every deletion path. Add an `Application` `deleting`
  model hook that deletes the application's `applications/{id}` directory, refactor
  `ApplicationController::destroy` to rely on it (DRY), and purge descendant application directories in
  the `Property`/`Unit`/`ApplicationLink` destroy paths via a shared helper.

## Medium

- **M1 — Mail and notifications are sent synchronously in the request path.**
  `ApplicationReceivedMail`, `WelcomeMail` and `NewApplicationNotification` use the `Queueable` trait but
  do **not** implement `ShouldQueue`; `SendWelcomeEmail` is a synchronous listener. A slow or failing
  SMTP server therefore 500s the public applicant *after* their application is already persisted (and
  there is no idempotency, so they may resubmit and create duplicates). `QUEUE_CONNECTION=database`, so
  queuing is effective. *Fix:* implement `ShouldQueue` on the two mailables and the notification, and
  make `SendWelcomeEmail` a queued listener.

- **M2 — No transaction around application + document creation.**
  In `PublicScreeningController::store`, the application is saved and then documents are created in a
  loop with no surrounding transaction; a mid-loop failure leaves an application with missing documents.
  *Fix:* wrap the application + documents writes in `DB::transaction()`.

- **M3 — Per-unit applicants list ships full models instead of the lean resource.**
  `ApplicationController::index` returns the raw paginated `Application` models (including `answers`,
  `form_snapshot`, `landlord_notes`) to the Inertia page, while `indexAll` and `indexForProperty` use
  `ApplicationRowResource`. Inconsistent and over-fetches. *Fix:* use `ApplicationRowResource` in
  `index` like the sibling list views.

## Low

- **L1 — Duplicated portfolio-counting logic.** `DashboardController::index` inlines the whole-vs-multi-unit
  space/occupied/available counting, and both it and `PropertyController::index` build the same
  `withCount([... occupied ... available ...])` query. *Fix:* extract the counting to `Property` accessor
  methods (or a shared query scope) and reuse.

- **L2 — Filter parsing duplicated.** `ApplicationController::indexAll` parses `search`/`status`/`property`
  inline and again inside `landlordApplicationsQuery`. *Fix:* parse once.

- **L3 — Missing explicit landlord authorization on the applications index/export.**
  `indexAll`/`exportAll` rely solely on `landlord_id` query scoping; a non-landlord authenticated user
  reaches them and gets empty results instead of a 403, unlike `PropertyController::index` which calls
  `authorize('viewAny', ...)`. *Fix:* add an explicit landlord gate for consistency and a correct 403.
