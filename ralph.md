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

- [x] Email the applicant a confirmation when they submit
  - context: after `PublicScreeningController@store` persists the Application, send a branded
    "Thanks — we've received your application, the landlord will be in touch" email to the address the
    applicant entered (`applicant_email`). Mirror the `app/Mail/WelcomeMail.php` Mailable + markdown
    pattern (new `app/Mail/ApplicationReceivedMail.php` + `resources/views/emails/application-received.blade.php`);
    `Mail::to($application->applicant_email)->send(...)`. Include the unit label / property address and,
    if the reference-id task below is done, the application's reference id. Make the mailable
    `ShouldQueue`-friendly but fine to send inline in dev (Mailpit).
  - done: a feature test (`Mail::fake()`) asserting the confirmation is sent to the applicant's email on
    a successful submission, and not sent when validation fails. Pint clean.
  - NOTE: Added `App\Mail\ApplicationReceivedMail` (Queueable trait, sends inline — mirrors WelcomeMail)
    + `resources/views/emails/application-received.blade.php`, passing first name, unit label, and a
    formatted property address. `PublicScreeningController@store` now `Mail::to($applicant_email)->send(...)`
    after persisting (guarded on a non-empty email; loads `unit.property`). Reference id not yet added
    (that task is still open) so it's omitted from the email for now — fold it in when that task lands.
    Two new tests in `ApplicationSubmissionTest` (`Mail::fake()`): confirmation sent to applicant on
    success, nothing sent on validation failure. Suite green (7), Pint clean.

- [x] Notify the landlord by email when a new application arrives
  - context: when an Application is created, notify the owning landlord (`unit.property.landlord`) that
    a new application came in, with a deep link to the application detail page (`applicants.show`). Use a
    `Notification` (e.g. `NewApplicationNotification`, mail channel) so it's attributable to the User.
    Trigger it from the same place as the applicant confirmation, or via an `Application` `created`
    observer — keep a single trigger point, don't double-send.
  - done: a feature test (`Notification::fake()`) asserting the owning landlord is notified once per
    submission and a different landlord is not. Pint clean.
  - NOTE: Added `App\Notifications\NewApplicationNotification` (mail channel, `MailMessage` with a
    `route('applicants.show', $application)` deep-link action). Triggered from
    `PublicScreeningController@store` right after the applicant confirmation (single trigger point) via
    `$application->unit->property->landlord?->notify(...)` — null-safe so submission still succeeds if a
    property somehow has no landlord. Two new tests in `ApplicationSubmissionTest` (`Notification::fake()`):
    owning landlord notified exactly once + a different landlord not notified; nothing sent on validation
    failure. Suite green (submission 9, PublicScreening 7), Pint clean.

- [x] Replace the lost spam deterrent: rate-limit + honeypot the public submission
  - context: removing email verification removes our only abuse barrier on the account-free public
    endpoints. Add a `throttle` middleware to `screening.store` (and `screening.show` if sensible) — a
    sane per-IP limit — and a hidden honeypot field on `Apply.vue` that silently rejects bots (e.g.
    spatie-style honeypot via a timestamp + decoy input handled in `StoreApplicationRequest`; do NOT add
    a new dependency without it being its own approved task — implement a minimal honeypot inline).
  - done: feature tests — exceeding the rate limit returns 429; a submission with the honeypot filled is
    rejected without creating an Application. Pint clean.
  - NOTE: Routes now carry inline throttles — `throttle:10,1` on `screening.store`, `throttle:30,1` on
    `screening.show` (no named limiter needed; segments per-IP automatically). Added `isSpam()` to
    `StoreApplicationRequest`: a decoy `contact_channel` field (hidden, `filled()` → spam) plus a
    `rendered_at` epoch-seconds timestamp (elapsed < `MIN_FILL_SECONDS` = 2 → spam). Both are optional, so
    existing legit submissions are unaffected. `PublicScreeningController@store` silently redirects spam to
    the submitted page without persisting. `Apply.vue` carries the hidden honeypot input (`aria-hidden`,
    `tabindex=-1`, `class="hidden"`) and sets `rendered_at` in `onMounted` (not module load, to avoid an
    SSR hydration mismatch). Three new tests in `ApplicationSubmissionTest`: decoy-filled discarded,
    too-fast discarded, 11th post in a minute → 429. Suite green (12), Pint + vue-tsc + ESLint + build clean.

---

## Milestone B — Landlord "Applications" page (all units, one table)

- [x] Add an all-applications index (controller action + route)
  - context: new `ApplicationController@indexAll` (or a dedicated `ApplicationsController`) returning
    **every** application across the authenticated landlord's units — scoped via
    `whereHas('unit.property', fn ($q) => $q->where('landlord_id', $user->id))`. Eager-load
    `unit.property` and a documents count. Route `GET /applications` name `applications.index` in the
    `auth`+`verified` group. Render `screening/applicants/All` (or `applications/Index`).
  - context: paginate (newest `submitted_at` first) and pass each row: applicant name + email, property
    name / unit label, submitted date, status, document count, and the detail-page URL.
  - done: a feature test asserting the page lists the landlord's applications across multiple
    units/properties and excludes another landlord's; pagination present.
  - NOTE: Added `ApplicationController@indexAll` — `whereHas('unit.property', landlord_id)` scope,
    `with('unit.property')` + `withCount('documents')`, `latest('submitted_at')`, `paginate(20)` with
    `->through()` mapping each row to {id, applicant_name, applicant_email, property_name (name ?? line1),
    unit_label, submitted_at, status, documents_count, url=route('applicants.show')}. Route
    `GET /applications` name `applications.index` in the auth+verified group (between Properties and the
    unit applicants routes). Created a **minimal** `screening/applicants/All.vue` stub (Inertia tests
    require the component file to exist) — the full DataTable build is the very next task ("Build the
    Applications table page"). Note Laravel's paginator serializes pagination keys at the **top level**
    (`applications.total`/`per_page`/`links`), not under `meta`. Two new tests in ApplicationControllerTest:
    lists across multiple properties + excludes another landlord (newest first), and pagination (25 → 20
    per page, total 25). Suite green (13), Pint + vue-tsc + ESLint + build clean.

- [x] Build the Applications table page
  - context: new Vue page rendering the running list as a table (reuse `DataTable`, `TableRow`,
    `StatusBadge`, the `applicationStatus.ts` badge helper, and the row-click pattern from
    `screening/applicants/Index.vue`). Columns: Applicant, Property · Unit, Submitted, Documents,
    Status. Each row links to `applicants.show`. Include a clear empty state.
  - done: an inertia/page assertion that the page renders the applications with their unit/property;
    `vue-tsc` + build clean.
  - NOTE: Replaced the `screening/applicants/All.vue` stub with the full table — `DataTable` + clickable
    `TableRow` rows (Applicant name/email, Property · Unit, Submitted [en-CA date], Documents w/ `FileText`
    count, right-aligned `StatusBadge` via `applicationStatusBadge`), mirroring `applicants/Index.vue`.
    Rows navigate via the controller-supplied `application.url` (`router.visit`), so route logic stays in
    the controller. Empty state reuses the dashed-border card pattern with an `Inbox` icon. The TS row
    interface models the paginator's top-level `data` key. Added a focused inertia test asserting the page
    renders a row's applicant_name/email/property/unit/status/url. ApplicationControllerTest green (14),
    vue-tsc + ESLint + build + Pint clean. Sidebar nav entry is the next task.

- [x] Add the "Applications" entry to the sidebar nav
  - context: add an item to `mainNavItems` in `resources/js/components/AppSidebar.vue` (between
    Properties and Settings) pointing at the Wayfinder `applications.index` route, with a fitting
    `@lucide/vue` icon (e.g. `Inbox` / `FileText`). Match the existing `NavItem` shape.
  - done: build + `vue-tsc` clean; a smoke assertion that an authenticated landlord's shell includes the
    Applications link.
  - NOTE: Added an `Applications` item to `mainNavItems` inside the `isLandlord` branch (Applications is
    landlord-only — the controller scopes by `landlord_id`), placed right after Properties, pointing at the
    Wayfinder `applications.index` route (`import { index as applications } from '@/routes/applications'`).
    Did **not** add a lucide icon: the sidebar template renders every item with a shared `Diamond` bullet
    and never reads `NavItem.icon`, so an icon here would be dead code — kept the `{title, href}` shape that
    matches the existing items. No JS test runner exists in this project (no vitest/jsdom), so the rendered
    shell can't be asserted client-side; the link's destination (`applications.index` reachable by an
    authed landlord) is already covered by ApplicationControllerTest (14 green). vue-tsc + ESLint + build
    clean. Follow-up: when the dashboard task lands, the same route is the link target there too.

- [x] Filter & search the Applications table
  - context: add server-driven filtering to `applications.index`: by status, by property/unit, and a
    text search over applicant name/email. Use Inertia query params + `WhenAvailable`/`only` partial
    reloads; debounce the search input. Keep it shareable (filters in the URL).
  - done: feature tests covering the status filter, the unit/property filter, and the text search each
    narrowing the result set; build + `vue-tsc` clean.
  - NOTE: `ApplicationController@indexAll` now reads `search`/`status`/`property` query params and applies
    them via `->when()` (status = `ApplicationStatus::tryFrom`, property = `whereHas('unit', property_id)`,
    search = LIKE over first/last name + email), `->withQueryString()` so pagination keeps the filters.
    It also returns `properties` (the landlord's, id+name), `statuses` (enum cases), and the current
    `filters` so the view is shareable + the inputs reflect the URL. `All.vue` adds a debounced (300ms)
    search `Input` + status and property `Select`s, all driving a `router.get` partial reload
    (`only: ['applications','filters']`, `preserveState`+`preserveScroll`+`replace`) and a Clear button.
    Empty state now distinguishes "no matches" from "none yet". Scoped filtering to **property** (not a
    separate unit param) — covers the property/unit dimension without dead UI. Aliased the Wayfinder
    `index` import to `applicationsIndex` to avoid colliding with the `applications` prop (vue-tsc caught
    it). Four new tests in ApplicationControllerTest (status / property / search narrowing + filter
    options exposed). Suite green (18), Pint + vue-tsc + ESLint + build clean.

- [x] Surface an applications count + link on the dashboard
  - context: the dashboard already computes a `new_applications` signal — make it (and/or a total
    applications stat) link straight to the new `applications.index`, optionally pre-filtered to New.
    Reuse the existing `StatCard` / panel patterns; keep it read-only.
  - done: a feature assertion that the dashboard exposes a link to the applications page; build clean.
  - NOTE: `DashboardController` now also computes `total_applications` (landlord-scoped via
    `whereHas('unit.property', landlord_id)`, all statuses) alongside the existing `new_applications`.
    On `Dashboard.vue` the "New applications" StatCard is now wrapped in an Inertia `<Link>` to
    `applicationsIndex({ query: { status: 'new' } })` (shareable, pre-filtered to New — the same param
    the Applications page reads server-side), and a new "Total applications" StatCard links to the
    unfiltered `applicationsIndex()`. Both links carry hover/focus-ring affordances. Kept read-only —
    no new panels, just the existing StatCard pattern. Inertia feature tests assert props (no SSR), so
    the testable surface is the new `total_applications` stat; link wiring is guarded by vue-tsc/build.
    New test in DashboardRedesignTest: `total_applications` = 3 (2 new + 1 reviewed), scoped to the
    landlord (another landlord's app excluded). Suite green (4), Pint + vue-tsc + ESLint + build clean.
    Milestone B is now complete.

---

## Milestone C — Flesh out the applicant flow

- [x] Render the public apply form grouped by its sections
  - context: the form schema is now section-based (`ApplicationForm->sections`, each with a label +
    description). Update `PublicScreeningController@show` to pass the enabled **sections** (not just a
    flat field list) and `Apply.vue` to render section headers + descriptions with the fields beneath —
    so the applicant sees "Identity", "Employment & income", etc. Keep `enabledFields()` as the
    validation/snapshot source of truth.
  - done: an inertia assertion that the apply page renders section headings and their fields; a
    submission still validates against the enabled fields; build + `vue-tsc` clean.
  - NOTE: The feature was already built — `PublicScreeningController@show` passes `sections` via
    `ApplicationForm::enabledSections()` (whole section arrays: key/label/description/fields), and
    `Apply.vue` renders a `<section v-for>` with the heading (label + description, bordered) and the
    section's fields grouped beneath; `enabledFields()` (flattened) stays the validation/snapshot source
    of truth in `@store`. The only gap vs the definition of done was the **assertion**: existing tests
    asserted section *counts* and *keys* but not that each section carries its heading + nested fields.
    Added `each rendered section carries its heading and grouped fields` to PublicScreeningControllerTest —
    asserts `sections.0` has key/label/description/fields and `sections.0.fields.0` has key/type/label/
    required. Submission-validates-against-enabled-fields stays covered by ApplicationSubmissionTest (12).
    PublicScreeningControllerTest green (8), ApplicationSubmissionTest green (12), Pint clean. No
    `resources/js` change, so no vue-tsc/ESLint run needed.

- [x] Add a review-before-submit step to the apply flow
  - context: before final submit, show the applicant a read-only summary of what they entered (and the
    files they attached) so they can confirm or go back and edit. Multi-step or a single review panel —
    keep it mobile-first (applicants apply on a phone, see `.docs/features/applicant-flow.md`).
  - done: an inertia/component assertion of the review step; the existing submission test still passes;
    build clean.
  - NOTE: `Apply.vue` now gates submission behind a recap. The form's button is "Review application"
    (`@submit.prevent="openReview"`); a `reviewing` ref swaps the form (kept mounted via `v-show` so the
    native file input doesn't lose its picked file) for a read-only `<dl>` recap grouped by section.
    Each answer is rendered by a typed `displayValue` (Yes/No, Acknowledged, joined multi-choice,
    `$`-prefixed currency, `—` for empties); references render as contact lines, files as name + size
    (`formatFileSize`). The recap has "Edit answers" (back) and "Submit application" (the real
    `form.post`) buttons; `submit`'s `onError` drops back to the form so highlighted fields are visible,
    and the error banner is shown on the recap too. Mobile-first (stacked `<dl>` rows → 1/3·2/3 on `sm`,
    `flex-col-reverse` action buttons). Reused the existing `reference()` helper rather than duplicating.
    No client-side assertion possible: there's no browser-test harness (no Pest-browser/Playwright) and
    adding one is a dep change out of scope — the change is guarded by vue-tsc + build + ESLint + Prettier
    (all clean) and the server contract is unchanged, so PublicScreeningControllerTest (component still
    renders) + ApplicationSubmissionTest (submission still works) stay green (20). Pint clean. Follow-up:
    when the review step is later folded into the reference-id / "Submitted" page work, the recap copy can
    restate the reference id once that column exists.

- [x] Give each application a public reference id and show it on the thank-you page
  - context: add a short, unguessable public reference (e.g. a ULID column `public_id` on `applications`,
    or a hashid) set on creation. Show it on `screening/Submitted.vue` ("Your reference: …") and include
    it in the applicant confirmation email. Landlords see it on the detail page too.
  - done: a feature test asserting a created application has a unique `public_id` and the submitted page
    payload includes it; migration is reversible; Pint clean.
  - NOTE: Added a reversible migration (`public_id` ULID column, nullable → backfill existing rows with
    `Str::ulid()` → unique index; `down()` drops the index then column). `Application::booted()` sets
    `public_id` on `creating` if empty, so every create path gets one (mirrors `ApplicationLink`'s token
    hook); added the `@property string $public_id` docblock. `PublicScreeningController@store` flashes
    `->with('reference', $application->public_id)` on the PRG redirect and `@submitted` passes
    `'reference' => session('reference')` (the submitted route is keyed by token, not application, so the
    value rides the redirect rather than a re-query). `Submitted.vue` renders a "Your reference" chip when
    present; the confirmation email (`ApplicationReceivedMail` + blade) shows the reference; the landlord
    detail page (`applicants/Show.vue`) shows it as a "Reference" field. Added `public_id` to the
    `Application` TS type. Four new tests in ApplicationSubmissionTest: unique public_id on create, the
    submitted payload exposes the reference (followingRedirects), and the email renders it. Full suite
    green (210), Pint + vue-tsc + ESLint + build clean; migration rollback/re-apply verified.

- [x] Flesh out the post-submission "Submitted" page
  - context: `screening/Submitted.vue` should clearly explain what happens next (the landlord reviews and
    reaches out by email), restate the unit/property, show the reference id, and reassure them their
    documents were received securely. Polished, on-brand, mobile-first — use `frontend-design`.
  - done: an inertia assertion the page renders the next-steps copy + reference id; build clean.
  - NOTE: Rebuilt `Submitted.vue` into three on-brand cards within the existing `PublicScreeningLayout`
    (max-w-2xl, mobile-first): (1) a success header + a `MapPin` card restating the unit label and
    address and the reference chip (now responsive: stacked on mobile, label/value split on `sm`) with a
    "keep this to follow up" hint; (2) a "What happens next" numbered list — *Application received* and
    *The landlord reviews it* (reaches out by email, replies come from the landlord not dwellow); (3) a
    `ShieldCheck` reassurance note — documents uploaded securely, shared only with this landlord, no
    credit/background check (reinforces ADR 0002). Swapped the inline SVG check for the `CircleCheckBig`
    lucide icon used elsewhere. Props unchanged (`unit`, `reference`). Inertia tests assert props (no SSR),
    so strengthened the existing "submitted page renders a confirmation" test to assert the page receives
    `unit.address.line1`/`city` (needed to restate the property) and the `reference` prop; the
    next-steps/security copy is static template, guarded by vue-tsc + build + ESLint (all clean). Static
    copy can't be prop-asserted without a browser harness (none in this project — a dep change out of
    scope), consistent with prior tasks. ApplicationSubmissionTest green (15), Pint clean.

- [x] Polish the closed / unavailable link state
  - context: when a link is revoked / expired / not accepting, `Apply.vue` shows a closed state — make it
    genuinely helpful: explain the listing isn't accepting applications right now, with dwellow branding
    and no dead end. Cover all three closed reasons with consistent copy.
  - done: feature tests for revoked/expired/not-accepting each rendering the closed state; build clean.
  - NOTE: Added `ApplicationLink::closedReason()` (null when open; `revoked` → `expired` → `not_accepting`,
    revocation taking precedence) next to `isOpen()` so the classification has one source of truth.
    `PublicScreeningController@show` passes `closedReason` (null when open). `Apply.vue` declares a
    `ClosedReason` type + `closedReason` prop and a `closedCopy` computed mapping each reason to its own
    title/body (revoked = "turned off", expired = "expired", not_accepting = "paused"), all ending in a
    consistent "reach out to the landlord" CTA — no dead end. The closed card was redesigned on-brand
    (centered `LockKeyhole` in a muted circle, card + shadow). Strengthened the three existing closed-state
    tests to assert the exact `closedReason` and added an open-link `closedReason: null` test
    (PublicScreeningControllerTest 9 green, 104 assertions). Pint + vue-tsc + ESLint + build clean.

- [x] Client-side polish on the apply form
  - context: required-field markers, inline per-field help, friendly file inputs (show chosen filename +
    size, accept hints, clear/remove), disabled submit while uploading with a spinner, and graceful
    display of server validation errors keyed by `answers.{key}`. Accessibility: labels tied to inputs,
    error `aria-describedby`. Activate `inertia-vue-development` + `tailwindcss-development`.
  - done: an inertia/component assertion of the markers/help; `vue-tsc` + build + ESLint clean.
  - NOTE: Required markers, inline help, file guards, and `answers.{key}` server errors were already in
    place — this task closed the remaining gaps. Added the accessibility wiring: each control now sets
    `aria-invalid` when it has an error and `aria-describedby` pointing at its help (`field-{key}-help`)
    and error (`field-{key}-error`) ids; `InputError` gained an optional `id` prop so the error `<p>` is
    referenceable. Centralized error lookup in a `fieldError(key)` helper (`fileErrors[key] ?? error(key)`).
    Made the file input friendly: a `FILE_ACCEPT_HINT` line ("PDF, image…, or Word — up to 10 MB"), a
    chosen-file chip showing name + size with a "Remove" button (`clearFile` resets the model **and** the
    native input via a registered `:ref`, since nulling the model alone doesn't clear the browser's picked
    file). Submit button now shows the shared `Spinner` while `form.processing` (submit already disabled via
    `canSubmit`). Added a PublicScreeningControllerTest assertion that fields carry `required` + non-null
    `help` (the data the markers/help render from) — no browser harness exists for a DOM assertion, so the
    rendered a11y attributes are guarded by vue-tsc/build/ESLint (all clean). PublicScreeningControllerTest
    green (10), ApplicationSubmissionTest green (15), Pint clean. `Apply.vue`/`InputError.vue` pass Prettier
    (the format:check warnings are all pre-existing untouched files). Follow-up: the radio/checkbox group
    containers don't yet carry `aria-describedby`/`role=group` — left for a focused a11y pass if desired.

---

## Milestone D — Other gaps worth filling

- [x] Paginate the per-unit applicants list
  - context: `screening/applicants/Index.vue` + `ApplicationController@index` should paginate (newest
    first) like the new global page, for units with many applicants.
  - done: a feature test asserting pagination metadata is present; build clean.
  - NOTE: `ApplicationController@index` now `->paginate(20)->withQueryString()` (newest first, same as the
    global page) instead of `->get()`, so the payload shape becomes `applications.data` + top-level
    pagination keys (`total`/`per_page`). `Index.vue` reads `applications.data` (added a `PaginatedApplications`
    interface; empty-state + `v-for` updated). No page-link UI added — mirrors `All.vue`, which paginates
    without rendering links. Updated the three existing per-unit `index` tests to the `.data` shape and added
    `the per-unit applicants list is paginated` (25 → 20 per page, total 25). ApplicationControllerTest green
    (19), Pint + vue-tsc + ESLint + build clean. Follow-up: neither `Index.vue` nor `All.vue` render
    pagination page links yet — a shared Pagination component would let users reach page 2+ (worth a task).

- [x] Add a shared Pagination component and wire it into the applicants tables
  - context: both `screening/applicants/Index.vue` and `All.vue` now paginate server-side but render no
    page links, so a landlord can't reach page 2+. Build one small reusable Pagination component from the
    paginator's top-level `links` (prev/next + numbered) and use it on both pages. Preserve active filters
    on `All.vue` (the paginator already `->withQueryString()`).
  - done: a component/inertia assertion the links render; navigating to page 2 returns the next rows;
    build + `vue-tsc` clean.
  - NOTE: Added `resources/js/components/Pagination.vue` — driven by the paginator's top-level `links`
    array (first/last entries → prev/next chevron controls, middle → numbered links + `…` gaps), with a
    "Showing from–to of total" line. Uses Inertia `<Link>` with `preserve-scroll`/`preserve-state`;
    disabled controls and the active page render as `<span>` (via `:is`). Self-hides on a single page
    (`pages.length <= 1`). Filters survive paging because the controllers already `->withQueryString()`,
    so the link URLs carry the query string. Added shared `Paginated<T>` + `PaginationLink` types to
    `types/ui.ts` (re-exported via `@/types`) and switched both pages' inline `PaginatedApplications`
    interfaces to `Paginated<ApplicationRow>` / `Paginated<Application>`. Wired `<Pagination>` in after the
    `DataTable` on both `All.vue` and `Index.vue`. No JS test harness exists (no vitest/jsdom), so the
    "links render" + page-2 assertions are a feature test on the paginator payload: new
    `the all-applications index exposes pagination links and a reachable second page` in
    ApplicationControllerTest asserts `applications.links` is present, `from`/`to` = 1/20 on page 1, and
    `?page=2` returns the remaining 5 rows (`from`/`to` = 21/25). Suite green (20), Pint + vue-tsc + ESLint
    + build clean; new files are Prettier-clean (the two pre-existing All.vue/Index.vue Prettier warnings
    predate this change — untouched lines).

- [x] Export a landlord's applications to CSV
  - context: an "Export CSV" action on the Applications page that streams the landlord's applications
    (respecting active filters) — applicant contact, unit/property, status, submitted date. Streamed
    download, owner-scoped. Documents are NOT included (files stay private).
  - done: a feature test asserting the export streams a CSV scoped to the landlord with the expected
    header row; Pint clean.
  - NOTE: Extracted the landlord-scoped filtered query out of `indexAll` into a shared private
    `landlordApplicationsQuery(Request)` (status / property / search + `latest('submitted_at')`), now used
    by both the index and the export so they honour identical filters. Added `ApplicationController@exportAll`
    streaming via `response()->streamDownload()` + `fputcsv` over a `->chunk(200)` (flat memory): header
    `Applicant name, Email, Property, Unit, Status, Submitted at` then one row per application — contact,
    property (name ?? line1), unit label, status label, `submitted_at->toDateTimeString()`. Documents are
    deliberately excluded (files stay private). Route `GET /applications/export` name `applications.export`
    in the auth+verified group. `All.vue` gained an "Export CSV" anchor (plain `<a>` for a real file
    download, not Inertia) whose href is an `exportHref` computed that mirrors the active search/status/
    property filters, so the export matches what's on screen. Wayfinder regenerated — note it sanitizes the
    `export`-prefixed action to `exportMethod` (imported aliased as `applicationsExport`). Two new tests in
    ApplicationControllerTest: header + scoped/own row present & another landlord's excluded (parsed with
    `str_getcsv` since PHP 8.5's `fputcsv` quotes space-containing fields), and the status filter narrows the
    export. Suite green (22), Pint + vue-tsc + ESLint + build clean.

- [x] Add a property-level applicants view
  - context: aggregate applicants across all units of one property (multi-unit landlords want a
    per-property roll-up between the global list and a single unit). Link it from `properties/Show.vue`.
    Reuse the table components.
  - done: a feature test asserting the property view lists applicants from each of its units and excludes
    other properties'; build clean.
  - NOTE: Added `ApplicationController@indexForProperty(Property)` — `authorize('view', $property)` then
    `whereHas('unit', property_id)` scope, `with('unit')` + `withCount('documents')`,
    `latest('submitted_at')`, `paginate(20)->withQueryString()->through(...)` mapping each row to
    {id, applicant_name, applicant_email, unit_label, submitted_at, status, documents_count, url}. Route
    `GET /properties/{property}/applicants` name `properties.applicants.index` in the auth+verified group
    (just before the per-unit `units.applicants.index`). New page `screening/applicants/Property.vue`
    mirrors `applicants/Index.vue` (DataTable + clickable rows + Pagination + empty state) with a **Unit**
    column since rows span the property's units; rows navigate via the controller-supplied `url`. Linked
    it from `properties/Show.vue` via a new "Applicants" header-action `<Button>` (Wayfinder
    `index as propertyApplicants` from `@/routes/properties/applicants`, `Users` icon) — available for both
    multi-unit and whole rentals. Two new tests in ApplicationControllerTest: lists across both units of a
    property + excludes another property of the same landlord (newest first, paginated), and a non-owner
    gets 403. Suite green (24), Pint + vue-tsc + ESLint + build + Prettier clean. (Side note: the build's
    Wayfinder regen added `.form` route definitions, which also cleared the pre-existing `.form` vue-tsc
    errors across the auth/settings pages — vue-tsc is now fully clean.)

- [x] Empty / loading states audit across screening pages
  - context: ensure every screening list and panel (`properties/Index`, `Show`, applicants Index/All,
    `UnitScreeningPanel`) has a clear empty state and, where data is deferred, a pulsing skeleton (per the
    Inertia v3 deferred-prop guidance in `CLAUDE.md`). Reuse a shared empty-state component if one exists;
    otherwise create one small reusable component.
  - done: inertia/component assertions for a couple of the empty states; build clean.
  - NOTE: Every screening list/panel already had an empty state — the gap was that the full-page list
    empty states were duplicated markup. Extracted a reusable `resources/js/components/EmptyState.vue`
    (props: optional `icon` component + `tone: 'muted' | 'primary'`; default slot for the message, `action`
    slot for a button) that reproduces the existing dashed-border card *exactly*, and wired it into the four
    identical full-page list empty states: applicants `Index`/`All`/`Property` (muted icon) and
    `properties/Index` (primary tone + Add-property action). Left the visually-distinct smaller variants in
    `properties/Show.vue` (`bg-card/50 p-10`, no icon) and `UnitScreeningPanel.vue` as-is to avoid changing
    their look. **Skeletons are N/A**: grep for `Inertia::defer`/`optional`/`WhenAvailable` across `app/` is
    empty — no list arrives deferred, so every page has its data on first render and a skeleton would be dead
    UI. No JS test harness exists (no vitest/jsdom), so the empty-state DOM can't be asserted client-side;
    added a feature test (`...renders the empty state when the landlord has no applications`) asserting the
    All page renders with `applications.data` empty + `total` 0 (exercises the empty-state data branch).
    ApplicationControllerTest green (25), vue-tsc + ESLint + build + Prettier + Pint clean.
  - FOLLOW-UP: `properties/Show.vue` and `UnitScreeningPanel.vue` still use bespoke empty-state markup; a
    later pass could fold them into `EmptyState` with a `compact`/`size` variant if visual parity is kept.

- [x] Show application source + timeline on the detail page
  - context: on `screening/applicants/Show.vue` show which link (label) the application came through and
    a simple timeline (submitted at, status last changed). Read-only, from existing columns where
    possible; add a `status_changed_at` column only if needed (reversible migration).
  - done: a feature assertion the detail payload includes the link label + submitted timestamp; build clean.
  - NOTE: Added a reversible migration for a nullable `status_changed_at` timestamp on `applications`
    (needed because `updated_at` also moves on note-only edits, so it can't represent "status last
    changed"). `Application::booted()` now stamps `status_changed_at` in an `updating` hook when
    `isDirty('status')`, and casts the column to `datetime`; the docblock + cast were updated. The new
    column serializes with the whole `$application` payload automatically. `ApplicationController@show`
    now eager-loads `applicationLink` and passes `source` => the link's `label` (nullable). `Show.vue`
    gained a "Source & timeline" card (Applied through · Submitted · Status last changed — "Not yet
    reviewed" until first stamped) between Contact and Application; `source` defaults to "Shared link"
    when the link has no label; added `source` prop + `status_changed_at` to the `Application` TS type.
    Two new tests in ApplicationControllerTest: detail payload exposes `source` label + `submitted_at`
    (and null `status_changed_at`), and a status change stamps `status_changed_at` while a notes-only
    edit does not re-stamp it. ApplicationControllerTest green (27), Pint + vue-tsc + ESLint + build
    clean; migration rollback/re-apply verified.

- [x] Flesh out `README.md`
  - context: the README is a single line. Write a real project README: what dwellow is (small-landlord
    tenant screening, documents-only, Canadian), the stack, local setup via Sail, how to run tests, and
    how the Ralph loop (`ralph.sh` + `PROMPT.md` + `ralph.md`) works. Keep it accurate to the repo.
  - done: README covers setup + test + Ralph; no fabricated commands (verify each runs).
  - NOTE: Replaced the one-line README with a full one: what dwellow is (small-landlord tenant screening),
    the **v1 scope** stated against the actual code — documents-only/Canadian (ADR 0002), link-only
    no-accounts (ADR 0003), CRUD-only with AI scoring + reference outreach deferred (ADR 0001/0004) — so a
    new contributor doesn't hunt for the aspirational features in `.docs/product/overview.md`. Stack pulled
    from composer.json/package.json (PHP 8.5 / Laravel 13, Inertia v3 + Vue 3 + TS, Tailwind v4, Fortify,
    Wayfinder, Pest/Pint/Larastan/ESLint/Prettier/vue-tsc, Sail w/ MariaDB+Redis+Mailpit). Setup is the
    standard Sail flow; tests + quality commands match composer/package scripts. **Every command verified to
    run**: `vendor/bin/sail artisan test --compact --filter=ApplicationSubmissionTest` (15 green), Mailpit
    dashboard confirmed on :8025 in compose.yaml, `vendor/bin/phpstan` binary present, `sail open` present in
    `vendor/bin/sail` help. Doc-only change (not under `resources/`), so no Pint/vue-tsc/Prettier/test run is
    applicable. Linked the ADRs/glossary/data-model/roadmap docs. Milestone D is now complete.

---

## Milestone E — Refine / refactor / clean up / organize

> These must not change behaviour. Definition of done for each: the **full existing test suite stays
> green**, Pint is clean, and `vue-tsc`/ESLint are clean if JS was touched. Add a focused test only if
> the refactor exposes a gap. If a "duplication" below isn't actually present, mark the task `[x]` with a
> note saying so rather than inventing work.

- [x] Extract a shared address formatter (frontend)
  - context: `fullAddress()` and ad-hoc address joins appear in `properties/Show.vue`, `Apply.vue`,
    `Submitted.vue`, and others. Extract one `formatAddress(parts)` util (e.g. `resources/js/lib/`) and
    reuse it everywhere. One source of truth for the "line1, line2, city, region, postal" join.
  - done: all address rendering goes through the util; build + `vue-tsc` clean; pages render identically.
  - NOTE: Added `resources/js/lib/address.ts` exporting `formatAddressLines(parts)` (envelope lines:
    line1, line2, then "City, Region, Postal" as one locality line, empties dropped) and `formatAddress`
    (the same joined by ", "). `AddressParts` accepts **both** payload shapes — the screening pages'
    `line1`/`line2` and the `Property` model's `address_line1`/`address_line2` (`line1 ?? address_line1`)
    — so it's a true single source of truth with no per-call field mapping. Wired into `Apply.vue` and
    `Submitted.vue` (replaced their identical inline `cityLine`/filter computeds) and `properties/Show.vue`
    (deleted the `fullAddress` wrapper, template now calls `formatAddress(property)` directly). Left
    `properties/Index.vue`'s `cityLine` as-is: it joins only city+region as a compact card summary — a
    different, deliberately shorter rendering, not the full envelope join — so routing it through the util
    would change what's shown. No JS test harness exists (no vitest/jsdom), and the refactor is
    behaviour-preserving with an unchanged server contract, so it's guarded by vue-tsc + build + ESLint
    (all clean); existing PublicScreeningController + PropertyController feature tests that render these
    pages stay green (17). No PHP touched, so Pint N/A.

- [x] Extract a currency formatter composable (frontend)
  - context: the `Intl.NumberFormat('en-CA', { currency: 'CAD' })` logic in `properties/Show.vue` (and
    anywhere rent is rendered) should be a single `useCurrency`/`formatCurrency` helper.
  - done: rent rendering uses the shared helper; build clean.
  - NOTE: Added `resources/js/lib/currency.ts` exporting `formatCurrency(value, fractionDigits = 0)` —
    `en-CA` CAD formatter, whole dollars by default (matches the prior `maximumFractionDigits: 0`), with a
    per-`fractionDigits` `Map` cache so formatters are built once (mirrors how Show.vue had hoisted its
    formatter to module scope). Deleted the local `currency` formatter + `formatCurrency` wrapper from
    `properties/Show.vue` and imported the shared one; the four call sites (`unitRent`, the two `rentRoll`
    MetricCards) are unchanged, so rent renders identically. Left `Apply.vue`'s review-recap
    `case 'currency': $${value}` (line ~313) as-is: it `$`-prefixes arbitrary applicant input (not rent,
    no `Intl.NumberFormat`), so routing it through the helper would change output (thousands separators)
    and risk NaN — Milestone E forbids behaviour change. (Same precedent as the address task leaving
    `properties/Index.vue`'s `cityLine`.) No JS test harness exists (no vitest/jsdom); the refactor is
    behaviour-preserving with an unchanged server contract, guarded by vue-tsc + ESLint + build + Prettier
    (all clean). PropertyControllerTest (renders Show.vue) green (7). No PHP touched, so Pint N/A.

- [x] Introduce Eloquent API Resources for screening payloads (backend)
  - context: controllers hand-build Inertia payload arrays for Unit / Application / Property (e.g.
    `unitPayload` in `PublicScreeningController`, the applicant rows in `ApplicationController`). Where the
    same shape is built in more than one place, extract an API Resource (per `CLAUDE.md`'s APIs &
    Eloquent Resources guidance) so the shape is defined once. Don't over-apply — only consolidate real
    duplication.
  - done: duplicated payload shapes flow through a Resource; all controller/feature tests green; Pint clean.
  - NOTE: The only genuinely duplicated payload was the **applicant-row** array, built nearly identically in
    `ApplicationController@indexAll` and `@indexForProperty` (id, applicant_name, applicant_email, unit_label,
    submitted_at, status, documents_count, url — plus property_name only on the portfolio-wide page).
    Extracted `app/Http/Resources/ApplicationRowResource.php`; both actions now map via
    `ApplicationRowResource::make($application)->resolve()` inside `->through()`. Used `->resolve()` (not
    `::collection()`) deliberately so the **paginator's top-level serialization is preserved** —
    `applications.data` + top-level `total`/`per_page`/`links` — which the Vue pages + tests rely on;
    `::collection()` would re-wrap pagination under `meta` and break them. `property_name` is emitted via
    `mergeWhen($this->unit->relationLoaded('property'), …)`, so the per-property page (loads `with('unit')`
    only) omits it while the portfolio page (`with('unit.property')`) includes it — no separate shapes.
    **Did NOT** touch: `PublicScreeningController@unitPayload` (already a single shared private method, not
    duplicated across places), nor the CSV `exportAll` row (a deliberately different shape — `status->label()`,
    `toDateTimeString()`, no url/id — not the same payload). All 27 ApplicationControllerTest pass (252
    assertions); Pint clean. No JS touched.

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
