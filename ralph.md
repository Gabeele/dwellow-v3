# Fix Plan — Ralph task list

> Format: `- [ ] <task>` for todo, `- [x] <task>` when done, `- [blocked] <task> — reason` if stuck.
> One task = one commit. Most important / most foundational first.
> Context bullets give the agent what it needs; follow `PROMPT.md` for the loop rules and definition of done.

## Where things stand

The full screening flow is **built and in git history** (see "Done" at the bottom): per-unit
section-based application forms seeded from a dwellow default and toggled section-by-section,
shareable per-unit links, the public applicant flow, the landlord's per-unit applicants list /
detail / status / notes / document download, whole-rental parity (backing unit), and a dashboard
applicant signal. **Do NOT rebuild any of that** — every task below edits or extends existing code.

This plan does four things, in priority order:

1. **Drop applicant email verification**, and instead email the applicant a friendly confirmation
   when they submit (and notify the landlord). The verification code was a barrier we don't want.
2. **Give the landlord a single "Applications" page** — one running table of every application
   across all their units.
3. **Flesh out the applicant flow** properly — it should feel like a real, polished product.
4. A long list of **refine / refactor / clean-up / organize** tasks, plus other gaps worth filling.

Guardrails (unchanged — see `.docs/decisions/`):
- **CRUD only.** No AI scoring, no automated reference outreach (deferred — ADR 0001,
  `.docs/features/scoring-engine.md`). Do NOT build them.
- **Documents-only, Canadian, applicant-provided / unverified.** dwellow never pulls a
  credit/background bureau (ADR 0002). A "credit report" is a file the applicant uploads.
- **Link-only applicants, no accounts** (ADR 0003) — applicants are never asked to register.
- Use the glossary terms (`.docs/domain/glossary.md`); reuse existing components and design language
  from `properties/Show.vue`, `UnitScreeningPanel.vue`, and `resources/js/components/ui/*`.
- Activate the `inertia-vue-development`, `tailwindcss-development`, `frontend-design`,
  `laravel-best-practices`, and `pest-testing` skills as relevant.

---

## Milestone A — Remove email verification, add submission confirmation

- [x] Remove the applicant email-verification gate from submission (backend)
  - context: applicants must no longer prove their email with a code. Delete the verification path:
    the `screening.verify` route, `PublicScreeningController@verify`, `VerifyApplicationEmailRequest`,
    `App\Screening\EmailVerification`, `App\Notifications\ApplicationVerificationCodeNotification`,
    and `resources/views/emails/application-code.blade.php`. In `StoreApplicationRequest` drop the
    `verification_code` rule; in `PublicScreeningController@store` remove the `verification_code`
    lookup and the `EmailVerification` dependency. Submission now succeeds on a valid form alone.
  - context: delete `tests/Feature/ApplicationEmailVerificationTest.php` (its feature is gone) and
    fix any other test that sends a `verification_code` (e.g. `ApplicationSubmissionTest`) so the
    suite is green without it.
  - done: a feature test asserting a valid submission to an open link creates the Application with
    **no** verification step; `grep -ri "verification_code\|EmailVerification" app resources` returns
    nothing. Full suite green; Pint clean.
  - NOTE: Done backend-only per the one-task-per-iteration rule. Removed the `screening.verify`
    route, `verify()`, `EmailVerification` service, `VerifyApplicationEmailRequest`,
    `ApplicationVerificationCodeNotification`, the `application-code` view, the `verification_code`
    rule, and deleted `ApplicationEmailVerificationTest`; fixed `ApplicationSubmissionTest`.
    `grep` over `app`/`routes` is clean (only Fortify's *landlord* email-verification remains).
    `resources/js/pages/screening/Apply.vue` still references `verification_code` — that is the very
    next task ("Remove the 'Verify your email' UI from the public apply page"). Full suite green (191),
    Pint clean.

- [x] Remove the "Verify your email" UI from the public apply page
  - context: in `resources/js/pages/screening/Apply.vue` remove the email-verification block (the
    `useHttp` send/resend-code request, the code input, `codeSent`, and the submit-disabled-until-code
    logic). The Apply form now submits directly. Keep the rest of the form intact.
  - done: `vue-tsc` + `vendor/bin/sail npm run build` clean; the apply-page feature/inertia assertion
    still passes and no longer references a code field.
  - NOTE: Removed the `useHttp` verifier, `verification_code` form field, `codeSent`/`verifyError`/
    `verifyNotice` refs, `sendCode`, the email-change `watch`, the verification UI block, and the
    submit-disabled-until-code message; `canSubmit` is now just `!form.processing`. Dropped the now-unused
    `useHttp`/`ref`/`watch` imports. `grep` over `resources/js` for the removed symbols is empty.
    vue-tsc + build clean; PublicScreeningController + ApplicationSubmission tests green (12).

- [ ] Email the applicant a confirmation when they submit
  - context: after `PublicScreeningController@store` persists the Application, send a branded
    "Thanks — we've received your application, the landlord will be in touch" email to the address the
    applicant entered (`applicant_email`). Mirror the `app/Mail/WelcomeMail.php` Mailable + markdown
    pattern (new `app/Mail/ApplicationReceivedMail.php` + `resources/views/emails/application-received.blade.php`);
    `Mail::to($application->applicant_email)->send(...)`. Include the unit label / property address and,
    if the reference-id task below is done, the application's reference id. Make the mailable
    `ShouldQueue`-friendly but fine to send inline in dev (Mailpit).
  - done: a feature test (`Mail::fake()`) asserting the confirmation is sent to the applicant's email on
    a successful submission, and not sent when validation fails. Pint clean.

- [ ] Notify the landlord by email when a new application arrives
  - context: when an Application is created, notify the owning landlord (`unit.property.landlord`) that
    a new application came in, with a deep link to the application detail page (`applicants.show`). Use a
    `Notification` (e.g. `NewApplicationNotification`, mail channel) so it's attributable to the User.
    Trigger it from the same place as the applicant confirmation, or via an `Application` `created`
    observer — keep a single trigger point, don't double-send.
  - done: a feature test (`Notification::fake()`) asserting the owning landlord is notified once per
    submission and a different landlord is not. Pint clean.

- [ ] Replace the lost spam deterrent: rate-limit + honeypot the public submission
  - context: removing email verification removes our only abuse barrier on the account-free public
    endpoints. Add a `throttle` middleware to `screening.store` (and `screening.show` if sensible) — a
    sane per-IP limit — and a hidden honeypot field on `Apply.vue` that silently rejects bots (e.g.
    spatie-style honeypot via a timestamp + decoy input handled in `StoreApplicationRequest`; do NOT add
    a new dependency without it being its own approved task — implement a minimal honeypot inline).
  - done: feature tests — exceeding the rate limit returns 429; a submission with the honeypot filled is
    rejected without creating an Application. Pint clean.

---

## Milestone B — Landlord "Applications" page (all units, one table)

- [ ] Add an all-applications index (controller action + route)
  - context: new `ApplicationController@indexAll` (or a dedicated `ApplicationsController`) returning
    **every** application across the authenticated landlord's units — scoped via
    `whereHas('unit.property', fn ($q) => $q->where('landlord_id', $user->id))`. Eager-load
    `unit.property` and a documents count. Route `GET /applications` name `applications.index` in the
    `auth`+`verified` group. Render `screening/applicants/All` (or `applications/Index`).
  - context: paginate (newest `submitted_at` first) and pass each row: applicant name + email, property
    name / unit label, submitted date, status, document count, and the detail-page URL.
  - done: a feature test asserting the page lists the landlord's applications across multiple
    units/properties and excludes another landlord's; pagination present.

- [ ] Build the Applications table page
  - context: new Vue page rendering the running list as a table (reuse `DataTable`, `TableRow`,
    `StatusBadge`, the `applicationStatus.ts` badge helper, and the row-click pattern from
    `screening/applicants/Index.vue`). Columns: Applicant, Property · Unit, Submitted, Documents,
    Status. Each row links to `applicants.show`. Include a clear empty state.
  - done: an inertia/page assertion that the page renders the applications with their unit/property;
    `vue-tsc` + build clean.

- [ ] Add the "Applications" entry to the sidebar nav
  - context: add an item to `mainNavItems` in `resources/js/components/AppSidebar.vue` (between
    Properties and Settings) pointing at the Wayfinder `applications.index` route, with a fitting
    `@lucide/vue` icon (e.g. `Inbox` / `FileText`). Match the existing `NavItem` shape.
  - done: build + `vue-tsc` clean; a smoke assertion that an authenticated landlord's shell includes the
    Applications link.

- [ ] Filter & search the Applications table
  - context: add server-driven filtering to `applications.index`: by status, by property/unit, and a
    text search over applicant name/email. Use Inertia query params + `WhenAvailable`/`only` partial
    reloads; debounce the search input. Keep it shareable (filters in the URL).
  - done: feature tests covering the status filter, the unit/property filter, and the text search each
    narrowing the result set; build + `vue-tsc` clean.

- [ ] Surface an applications count + link on the dashboard
  - context: the dashboard already computes a `new_applications` signal — make it (and/or a total
    applications stat) link straight to the new `applications.index`, optionally pre-filtered to New.
    Reuse the existing `StatCard` / panel patterns; keep it read-only.
  - done: a feature assertion that the dashboard exposes a link to the applications page; build clean.

---

## Milestone C — Flesh out the applicant flow

- [ ] Render the public apply form grouped by its sections
  - context: the form schema is now section-based (`ApplicationForm->sections`, each with a label +
    description). Update `PublicScreeningController@show` to pass the enabled **sections** (not just a
    flat field list) and `Apply.vue` to render section headers + descriptions with the fields beneath —
    so the applicant sees "Identity", "Employment & income", etc. Keep `enabledFields()` as the
    validation/snapshot source of truth.
  - done: an inertia assertion that the apply page renders section headings and their fields; a
    submission still validates against the enabled fields; build + `vue-tsc` clean.

- [ ] Add a review-before-submit step to the apply flow
  - context: before final submit, show the applicant a read-only summary of what they entered (and the
    files they attached) so they can confirm or go back and edit. Multi-step or a single review panel —
    keep it mobile-first (applicants apply on a phone, see `.docs/features/applicant-flow.md`).
  - done: an inertia/component assertion of the review step; the existing submission test still passes;
    build clean.

- [ ] Give each application a public reference id and show it on the thank-you page
  - context: add a short, unguessable public reference (e.g. a ULID column `public_id` on `applications`,
    or a hashid) set on creation. Show it on `screening/Submitted.vue` ("Your reference: …") and include
    it in the applicant confirmation email. Landlords see it on the detail page too.
  - done: a feature test asserting a created application has a unique `public_id` and the submitted page
    payload includes it; migration is reversible; Pint clean.

- [ ] Flesh out the post-submission "Submitted" page
  - context: `screening/Submitted.vue` should clearly explain what happens next (the landlord reviews and
    reaches out by email), restate the unit/property, show the reference id, and reassure them their
    documents were received securely. Polished, on-brand, mobile-first — use `frontend-design`.
  - done: an inertia assertion the page renders the next-steps copy + reference id; build clean.

- [ ] Polish the closed / unavailable link state
  - context: when a link is revoked / expired / not accepting, `Apply.vue` shows a closed state — make it
    genuinely helpful: explain the listing isn't accepting applications right now, with dwellow branding
    and no dead end. Cover all three closed reasons with consistent copy.
  - done: feature tests for revoked/expired/not-accepting each rendering the closed state; build clean.

- [ ] Client-side polish on the apply form
  - context: required-field markers, inline per-field help, friendly file inputs (show chosen filename +
    size, accept hints, clear/remove), disabled submit while uploading with a spinner, and graceful
    display of server validation errors keyed by `answers.{key}`. Accessibility: labels tied to inputs,
    error `aria-describedby`. Activate `inertia-vue-development` + `tailwindcss-development`.
  - done: an inertia/component assertion of the markers/help; `vue-tsc` + build + ESLint clean.

---

## Milestone D — Other gaps worth filling

- [ ] Paginate the per-unit applicants list
  - context: `screening/applicants/Index.vue` + `ApplicationController@index` should paginate (newest
    first) like the new global page, for units with many applicants.
  - done: a feature test asserting pagination metadata is present; build clean.

- [ ] Export a landlord's applications to CSV
  - context: an "Export CSV" action on the Applications page that streams the landlord's applications
    (respecting active filters) — applicant contact, unit/property, status, submitted date. Streamed
    download, owner-scoped. Documents are NOT included (files stay private).
  - done: a feature test asserting the export streams a CSV scoped to the landlord with the expected
    header row; Pint clean.

- [ ] Add a property-level applicants view
  - context: aggregate applicants across all units of one property (multi-unit landlords want a
    per-property roll-up between the global list and a single unit). Link it from `properties/Show.vue`.
    Reuse the table components.
  - done: a feature test asserting the property view lists applicants from each of its units and excludes
    other properties'; build clean.

- [ ] Empty / loading states audit across screening pages
  - context: ensure every screening list and panel (`properties/Index`, `Show`, applicants Index/All,
    `UnitScreeningPanel`) has a clear empty state and, where data is deferred, a pulsing skeleton (per the
    Inertia v3 deferred-prop guidance in `CLAUDE.md`). Reuse a shared empty-state component if one exists;
    otherwise create one small reusable component.
  - done: inertia/component assertions for a couple of the empty states; build clean.

- [ ] Show application source + timeline on the detail page
  - context: on `screening/applicants/Show.vue` show which link (label) the application came through and
    a simple timeline (submitted at, status last changed). Read-only, from existing columns where
    possible; add a `status_changed_at` column only if needed (reversible migration).
  - done: a feature assertion the detail payload includes the link label + submitted timestamp; build clean.

- [ ] Flesh out `README.md`
  - context: the README is a single line. Write a real project README: what dwellow is (small-landlord
    tenant screening, documents-only, Canadian), the stack, local setup via Sail, how to run tests, and
    how the Ralph loop (`ralph.sh` + `PROMPT.md` + `ralph.md`) works. Keep it accurate to the repo.
  - done: README covers setup + test + Ralph; no fabricated commands (verify each runs).

---

## Milestone E — Refine / refactor / clean up / organize

> These must not change behaviour. Definition of done for each: the **full existing test suite stays
> green**, Pint is clean, and `vue-tsc`/ESLint are clean if JS was touched. Add a focused test only if
> the refactor exposes a gap. If a "duplication" below isn't actually present, mark the task `[x]` with a
> note saying so rather than inventing work.

- [ ] Extract a shared address formatter (frontend)
  - context: `fullAddress()` and ad-hoc address joins appear in `properties/Show.vue`, `Apply.vue`,
    `Submitted.vue`, and others. Extract one `formatAddress(parts)` util (e.g. `resources/js/lib/`) and
    reuse it everywhere. One source of truth for the "line1, line2, city, region, postal" join.
  - done: all address rendering goes through the util; build + `vue-tsc` clean; pages render identically.

- [ ] Extract a currency formatter composable (frontend)
  - context: the `Intl.NumberFormat('en-CA', { currency: 'CAD' })` logic in `properties/Show.vue` (and
    anywhere rent is rendered) should be a single `useCurrency`/`formatCurrency` helper.
  - done: rent rendering uses the shared helper; build clean.

- [ ] Introduce Eloquent API Resources for screening payloads (backend)
  - context: controllers hand-build Inertia payload arrays for Unit / Application / Property (e.g.
    `unitPayload` in `PublicScreeningController`, the applicant rows in `ApplicationController`). Where the
    same shape is built in more than one place, extract an API Resource (per `CLAUDE.md`'s APIs &
    Eloquent Resources guidance) so the shape is defined once. Don't over-apply — only consolidate real
    duplication.
  - done: duplicated payload shapes flow through a Resource; all controller/feature tests green; Pint clean.

- [ ] Consolidate the `firstOrCreate` default-form logic
  - context: `ApplicationFormController@edit` and `@update` both call
    `firstOrCreate([], ['sections' => DefaultApplicationForm::sections()])`. Extract to a single method
    (e.g. `Unit::applicationFormOrDefault()` or a small action) so the seed lives in one place.
  - done: both call sites use the shared method; tests green; Pint clean.

- [ ] Audit & dedupe the screening TypeScript types
  - context: consolidate the `Application`, `Unit`, `Property`, `ApplicationLink`, form-field/section
    types in `resources/js/types/` so pages import one canonical definition instead of redeclaring shapes
    inline (e.g. `FormField`/`SectionField` interfaces duplicated across pages).
  - done: pages import shared types; no duplicated interfaces; `vue-tsc` clean.

- [ ] Centralize status → badge variant mapping
  - context: ensure `ApplicationStatus` → badge variant (and any `OccupancyStatus` mapping) lives in one
    place (`applicationStatus.ts`) and every page uses it — no inline `match`/ternary duplicates.
  - done: one mapping, reused everywhere; build clean.

- [ ] Dead-code sweep after the verification removal
  - context: once Milestone A lands, grep for orphans — unused imports, routes, translations, cache keys,
    factory states, or test helpers left behind by the removed verification flow — and delete them.
  - done: `grep` for the removed symbols is empty; full suite green; Pint + ESLint clean.

- [ ] Run Larastan and fix what it flags
  - context: run `vendor/bin/sail php artisan` is not it — run Larastan (`vendor/bin/sail php vendor/bin/phpstan analyse` per `phpstan.neon`). Fix legitimate issues (missing types, generics on relations/collections, array shapes). Do not silence with baseline unless a finding is a genuine false positive.
  - done: Larastan passes at the configured level (or only documented, justified ignores remain); Pint clean.

- [ ] Tighten controller method docblocks & return types
  - context: sweep the screening controllers for consistent PHPDoc + explicit return types + array-shape
    annotations per the PHP rules in `CLAUDE.md`. No behaviour change.
  - done: Pint + Larastan clean; suite green.

- [ ] Extract shared validation rules for application fields
  - context: if field-rule construction or address/contact rules are duplicated across
    `StoreApplicationRequest` / `UpdateApplicationFormRequest` / property & unit requests, pull the
    common pieces into a Concern (see the existing `app/Concerns/` pattern).
  - done: shared rules live in one Concern; request tests green; Pint clean.

- [ ] Naming & glossary consistency pass
  - context: align code/UI vocabulary with `.docs/domain/glossary.md` (Applicant vs Application vs
    Application Link, etc.) — route names, variable names, page titles, button copy. Small, surgical
    renames only; keep public URLs stable.
  - done: terminology matches the glossary; suite green; build clean.

- [ ] Add a screening smoke test (Pest browser / page render)
  - context: a lightweight smoke test that visits the key landlord screening pages (properties show,
    form builder, applicants index, applications index) and the public apply page asserting no JS/render
    errors — per the `pest-testing` skill's smoke-testing guidance.
  - done: the smoke test passes locally against the built assets.

## Design & domain references

Read the relevant doc before a task: `.docs/features/applicant-flow.md`,
`application-form-builder.md`, `landlord-dashboard.md`, `.docs/domain/data-model.md`,
`.docs/domain/glossary.md`, and the ADRs in `.docs/decisions/`.

## Done — previous milestones (in git history)

Screening CRUD (enums, default form, models/migrations/factories/policies, auto-provisioned per-unit
form, form-builder + section toggles, links create/toggle/revoke, public apply + submission + snapshot +
documents, applicants list/detail/status/notes/delete, secure document download, dashboard signal) and
whole-rental parity (backing unit auto-provision + backfill + screening surface). The earlier email-code
verification was added and is now being **removed** in Milestone A. See `git log`.
