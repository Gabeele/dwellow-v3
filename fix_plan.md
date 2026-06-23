# Fix Plan — Ralph task list

> Format: `- [ ] <task>` for todo, `- [x] <task>` when done, `- [blocked] <task> — reason` if stuck.
> One task = one commit. Most important / most foundational first.
> Context bullets give the agent what it needs; follow `PROMPT.md` for the loop rules and definition of done.

## Milestone: Tenant screening — custom application forms

Build the v1 screening flow as **CRUD only — no AI inferencing, no scoring job, no automated
reference outreach** (those are explicitly deferred — see `.docs/features/scoring-engine.md`
and `references.md`; do NOT build them). The goal of this milestone:

1. A landlord configures a **custom application form** (set of fields) per **unit**, starting
   from a sensible dwellow **default**. Forms differ per unit.
2. The landlord generates a shareable **application link** (unguessable token) per unit, and
   can **toggle whether it's accepting submissions**, plus revoke/expire it.
3. A prospective tenant opens `/screening/{token}` (no account), fills the form, uploads
   documents, and submits — creating an **Application** with a **form snapshot**.
4. The landlord sees an **applicants** list per unit and a detail page, and can change an
   application's status / delete it. Pure CRUD.

Data model (confirmed — follows `.docs/domain/data-model.md`):

```
Unit
 ├─1:1─ ApplicationForm   (field schema as JSON, seeded from the default)
 └─1:N─ ApplicationLink   (token, is_accepting toggle, expires_at, revoked_at)
            └─1:N─ Application  (applicant contact, answers JSON, form_snapshot JSON, status)
                       └─1:N─ Document  (uploaded files: pay stub, ID, etc.)
```

References are modeled as a **field type within the form schema** (a "reference block"
field stored in the answers) — NOT a separate entity and NOT contacted in v1.

Read `.docs/features/application-form-builder.md`, `applicant-flow.md`, and
`landlord-dashboard.md` + `.docs/domain/glossary.md` before starting — use the glossary's
terms (Applicant, Application, Application Form, Application Link, etc.) in code and UI.

## Tasks

### Enums & the default form

- [x] Add a `FieldType` enum for application-form fields
  - context: new file `app/Enums/FieldType.php`. Mirror the structure of existing enums in
    `app/Enums/` (e.g. `RentalType.php` — backed string enum with a `label(): string` method
    using a `match`). Cases (use TitleCase keys per the PHP rules in `CLAUDE.md`):
    `ShortText`, `LongText`, `Number`, `Currency`, `Date`, `SingleChoice`, `MultiChoice`,
    `Boolean`, `File`, `Reference`, `Consent`. Values are snake_case strings
    (`short_text`, etc.).
  - context: add a helper `expectsOptions(): bool` (true for `SingleChoice`/`MultiChoice`) and
    `isFileUpload(): bool` (true for `File`) — the form builder and submission validator will
    need them.
  - done: a unit test (`tests/Unit/FieldTypeTest.php`) covering `label()` for a couple of cases
    and the two helper booleans.
  - note: Added `FieldType` enum with 11 cases, `label()`, `expectsOptions()`, `isFileUpload()`;
    `FieldTypeTest` (3 tests, 10 assertions) green.

- [x] Add an `ApplicationStatus` enum
  - context: new file `app/Enums/ApplicationStatus.php`, same pattern as above. Cases per the
    data-model lifecycle: `New`, `Reviewing`, `Approved`, `Rejected` (values `new`, `reviewing`,
    `approved`, `rejected`). Add `label()`.
  - done: a unit test covering `label()` and `ApplicationStatus::cases()` count.
  - note: Added `ApplicationStatus` enum (4 cases, `label()`); `ApplicationStatusTest`
    (2 tests, 5 assertions) green. Pint clean.

- [x] Define the dwellow default application form schema
  - context: new class `app/Screening/DefaultApplicationForm.php` (create the `app/Screening/`
    dir; use `make:class`). Expose `public static function fields(): array` returning the
    default ordered field schema as plain arrays. Each field is a shape:
    `['key' => string, 'type' => FieldType value, 'label' => string, 'required' => bool,
    'help' => ?string, 'options' => ?array<string>]`.
  - context: ground the defaults in standard landlord screening (see research summary below).
    Include these fields in this order:
    - Identity: `first_name`, `last_name` (short_text, required); `email`, `phone` (short_text,
      required); `date_of_birth` (date, required).
    - Current residence: `current_address` (short_text, required); `current_move_in_date`
      (date); `current_monthly_rent` (currency); `reason_for_leaving` (long_text).
    - Employment & income: `employer_name` (short_text, required); `job_title` (short_text);
      `employment_type` (single_choice: Full-time / Part-time / Self-employed / Student /
      Unemployed); `gross_monthly_income` (currency, required); `employment_start_date` (date).
    - Occupancy: `desired_move_in_date` (date, required); `number_of_occupants` (number,
      required); `has_pets` (boolean) + `pet_details` (long_text); `is_smoker` (boolean).
    - References: `previous_landlord` (reference — name/email/phone/relationship block).
    - Documents: `photo_id` (file, required); `pay_stubs` (file, required);
      `proof_of_income` (file) — label it "Bank statement or additional proof of income".
    - Consent: `screening_consent` (consent, required) — label/help should state the applicant
      consents to the landlord **using and verifying the information and contacting the
      references they've provided** (a privacy/consent acknowledgement under Canadian privacy
      law — PIPEDA / provincial equivalents). dwellow does NOT run credit/background checks or
      pull any numbers — applicants only submit their own info and documents — so frame consent
      around handling the provided data, not authorizing a bureau check.
  - context: do NOT collect a Social Insurance Number (SIN) — Canadian privacy guidance
    discourages collecting it on rental applications; leave it off the default form.
  - context: this list pairs with the (deferred) default scorecard — do NOT build scoring here.
  - done: a unit test asserting `fields()` returns the expected count, that every entry has the
    required keys, that `screening_consent` is present and required, and that each `type` is a
    valid `FieldType` value.
  - note: Added `DefaultApplicationForm::fields()` returning 24 ordered fields via a private
    `field()` builder (always emits key/type/label/required/help/options); `type` stored as the
    enum string value for clean JSON. `DefaultApplicationFormTest` (6 tests, 320 assertions) green,
    Pint clean.

### Data layer — models, migrations, factories

- [x] Create the `ApplicationForm` model, migration, and factory
  - context: `vendor/bin/sail artisan make:model ApplicationForm -mf`. Columns: `id`,
    `unit_id` (foreignId, constrained, cascade on delete, unique — one form per unit),
    `fields` (json), timestamps. Cast `fields` to `array`. Use the `#[Fillable([...])]`
    attribute pattern from `app/Models/Unit.php`. Add `unit(): BelongsTo`.
  - context: add the inverse `applicationForm(): HasOne` to `app/Models/Unit.php`.
  - context: factory default `fields` should use `DefaultApplicationForm::fields()`; add a
    `forUnit(Unit $unit)` state if helpful. `unit_id` defaults to `Unit::factory()`.
  - done: a feature test asserting a unit can have one application form and `fields` round-trips
    as an array.
  - note: Added `ApplicationForm` model (`#[Fillable]`, `fields`=>array cast, `unit()` BelongsTo),
    migration (`unit_id` unique FK cascade + json `fields`), and factory (default `fields` from
    `DefaultApplicationForm::fields()`, `forUnit()` state). Added `applicationForm(): HasOne` to
    `Unit`. `ApplicationFormTest` (3 tests, 5 assertions) green; Pint clean.

- [x] Auto-provision a default form when a unit is created
  - context: register a `UnitObserver` (`app/Observers/UnitObserver.php`, attach via the
    `#[ObservedBy]` attribute on `app/Models/Unit.php`) whose `created()` hook creates an
    `ApplicationForm` for the unit seeded from `DefaultApplicationForm::fields()` — but only if
    one doesn't already exist.
  - context: confirm units created through `UnitController@store` and through the factory both
    get a default form (the factory's `created` afterCreating may also need it — keep behavior
    consistent; prefer the observer as the single source of truth).
  - done: a feature test asserting that creating a `Unit` results in an `ApplicationForm` with
    the default fields; creating a second time does not duplicate it.
  - note: Added `UnitObserver` (`created()` uses `applicationForm()->firstOrCreate([], …)` so a
    re-fire is a no-op), attached via `#[ObservedBy(UnitObserver::class)]` on `Unit`. Observer is
    the single source of truth — fires for both `UnitController@store` (Eloquent relation create)
    and the factory; `UnitFactory` needs no afterCreating. Since every unit now auto-gets a form,
    updated `ApplicationFormTest` to derive the form from the unit (bare `ApplicationForm::factory()
    ->create()` would now collide on the unique `unit_id`). Added `UnitObserverTest` (2 tests).
    Full suite 120 passed; Pint clean.

- [x] Create the `ApplicationLink` model, migration, and factory
  - context: `make:model ApplicationLink -mf`. Columns: `id`, `unit_id` (foreignId, constrained,
    cascade), `token` (string, unique, indexed), `label` (string, nullable — landlord's name
    for the link e.g. "Facebook post"), `is_accepting` (boolean, default true), `expires_at`
    (timestamp, nullable), `revoked_at` (timestamp, nullable), timestamps. Cast booleans/dates.
  - context: generate the token with `Str::random(40)` in a model `creating` hook (or a
    `booted()` method) if not set — must be unguessable; route-model-bind by `token` later.
  - context: add helper methods `isOpen(): bool` (accepting AND not revoked AND not expired) and
    scopes/states. Add `unit(): BelongsTo` and `applications(): HasMany`. Add the inverse
    `applicationLinks(): HasMany` on `Unit`.
  - context: factory: `unit_id` => `Unit::factory()`; states `revoked()`, `expired()`,
    `notAccepting()`.
  - done: a feature test covering token auto-generation + uniqueness and `isOpen()` returning
    false for revoked / expired / not-accepting links and true otherwise.
  - note: Added `ApplicationLink` model (token via `booted()` `creating` hook using `Str::random(40)`
    so all creation paths get one; `isOpen()` = accepting ∧ ¬revoked ∧ ¬expired; `unit()` BelongsTo,
    `applications()` HasMany), migration (unit FK cascade, unique `token`, nullable `label`,
    `is_accepting` default true, `expires_at`/`revoked_at`), factory (`revoked()`/`expired()`/
    `notAccepting()` states). Added `applicationLinks(): HasMany` to `Unit`. `ApplicationLinkTest`
    (9 tests, 10 assertions) green; full suite 129 passed; Pint clean.

- [ ] Create the `Application` model, migration, and factory
  - context: `make:model Application -mf`. Columns: `id`, `application_link_id` (foreignId,
    constrained, cascade), `unit_id` (foreignId, constrained, cascade — denormalized for easy
    querying), `applicant_first_name`, `applicant_last_name`, `applicant_email`,
    `applicant_phone`, `answers` (json), `form_snapshot` (json — the field schema as submitted),
    `status` (string, default `new`), `landlord_notes` (text, nullable), `submitted_at`
    (timestamp, nullable), timestamps.
  - context: cast `answers`/`form_snapshot` to `array`, `status` to `ApplicationStatus`,
    `submitted_at` to `datetime`. Use `#[Fillable]`. Relations: `applicationLink()`, `unit()`,
    `documents(): HasMany`. Add `applications(): HasMany` to `Unit`.
  - context: factory: link to `ApplicationLink::factory()`, set matching `unit_id`, fake
    applicant contact, `answers`/`form_snapshot` from `DefaultApplicationForm::fields()`,
    `status` = New, `submitted_at` = now.
  - done: a feature test asserting an application persists with array casts intact and belongs to
    its link + unit.

- [ ] Create the `Document` model, migration, and factory
  - context: `make:model Document -mf`. Columns: `id`, `application_id` (foreignId, constrained,
    cascade), `field_key` (string — which form field it answers), `disk` (string, default
    `local`), `path` (string), `original_name` (string), `mime_type` (string, nullable), `size`
    (unsignedBigInteger, nullable), timestamps. Use `#[Fillable]`. Add `application(): BelongsTo`.
  - context: documents live on the **private** `local` disk (see `config/filesystems.php` —
    root `storage/app/private`, not public). Never store under `public/`.
  - done: a feature test asserting a document belongs to an application and the columns persist.

- [ ] Add ownership authorization policies for the screening entities
  - context: landlords may only touch screening data for units of properties they own. Mirror
    `app/Policies/UnitPolicy.php` (which checks `$user->isLandlord() && $unit->property->landlord_id === $user->id`).
    Add policies: `ApplicationFormPolicy` (view/update), `ApplicationLinkPolicy`
    (view/create/update/delete), `ApplicationPolicy` (view/update/delete), `DocumentPolicy`
    (view/download). Each resolves ownership by walking to `…->unit->property->landlord_id`.
  - context: Laravel 13 auto-discovers policies by naming convention — verify no manual
    registration is needed (the existing Property/Unit policies aren't registered manually).
  - done: a feature test per policy asserting the owning landlord is allowed and a different
    landlord is denied.

### Landlord — application form builder

- [ ] Add backend routes + controller to view and update a unit's application form
  - context: new `ApplicationFormController` with `edit(Unit $unit)` (Inertia render
    `screening/forms/Edit`, pass the unit + its form `fields`) and `update(Request, Unit $unit)`
    (persist the edited schema). Authorize with the `ApplicationForm`/`Unit` policy. Nest under
    the unit: e.g. `Route::get('units/{unit}/form', …)->name('units.form.edit')` and a matching
    PUT, inside the existing `auth`+`verified` group in `routes/web.php`.
  - context: validate the submitted schema with a `FormRequest` (`UpdateApplicationFormRequest`)
    — each field must have a unique `key`, a valid `FieldType`, a `label`, a boolean `required`,
    and `options` only when the type `expectsOptions()`. Extract shared rules into a Concern if
    it grows (see `app/Concerns/UnitValidationRules.php` pattern).
  - context: use `Inertia::flash('toast', …)` and `to_route(...)` like `UnitController`.
  - done: feature tests — owner can load the edit page (Inertia component assertion); owner can
    PUT a valid schema and it persists; invalid schema (dup key / bad type / options on a text
    field) is rejected; a non-owner gets 403.

- [ ] Build the form-builder UI page
  - context: new `resources/js/pages/screening/forms/Edit.vue` using `AppLayout` and existing
    `resources/js/components/ui/*` (input, select, checkbox, button, card, separator). Let the
    landlord add / edit / remove / **reorder** fields, set the label, pick a `FieldType`, mark
    required, and edit choice options when the type needs them. Posts the full `fields` array
    via Inertia `useForm` to `units.form.edit`'s PUT route (use Wayfinder-generated actions per
    `CLAUDE.md`).
  - context: provide a "Reset to dwellow default" affordance that repopulates from the default
    (a button that loads `DefaultApplicationForm` — surface the default via a prop). Show the
    field-type list from a `fieldTypes` prop (value+label) the controller passes.
  - context: this is meaningful UX — activate the `inertia-vue-development`, `frontend-design`,
    and `tailwindcss-development` skills. Vue components need a single root element.
  - done: an Inertia/feature assertion that the edit page renders with the unit's fields and the
    `fieldTypes` prop; verify `vendor/bin/sail npm run build` + `vue-tsc` are clean.

### Landlord — application links

- [ ] Add backend for creating / toggling / revoking application links
  - context: new `ApplicationLinkController` with `store(Unit $unit)` (create a link — token
    auto-generated; optional `label`), `update(ApplicationLink $link)` (toggle `is_accepting`,
    set/clear `expires_at`), and `destroy(ApplicationLink $link)` (set `revoked_at` — soft
    revoke, don't hard-delete so historical applications keep their link). Authorize via
    `ApplicationLinkPolicy`. Routes nested under the unit in the `auth`+`verified` group.
  - context: don't expose the raw DB id in the public URL — the public route uses the `token`.
  - done: feature tests — owner can create a link (token present, accepting by default), toggle
    accepting off, set an expiry, and revoke it (`revoked_at` set, `isOpen()` false); non-owner
    403 on each.

- [ ] Surface link management + the applicants entry point on the unit
  - context: on the property show page (`resources/js/pages/properties/Show.vue`, served by
    `PropertyController@show`) or a dedicated unit view, show each unit's application link(s):
    the shareable URL (built from the token — use `route()`/Wayfinder so the domain is correct),
    a copy-to-clipboard button, the accepting toggle, an expiry/revoke control, and a link to
    that unit's **applicants** list. Eager-load `applicationLinks` (+ applications count) in the
    controller.
  - context: reuse existing display patterns (DataTable / badges) already in `Show.vue`. Keep
    the public URL obviously copyable. Use the `success` green accent per existing design.
  - done: a feature test asserting the show response includes a unit's links and applicant
    counts; build + vue-tsc clean.

### Applicant — public screening flow (no account)

- [ ] Add the public screening route + controller (resolve token, gate state)
  - context: new `PublicScreeningController@show` bound to `/screening/{token}` (route name
    `screening.show`) — **outside** the `auth`/`verified` group (applicants have no account).
    Route-model-bind `ApplicationLink` by its `token` column (define `getRouteKeyName()` or bind
    explicitly). Eager-load `unit.property` and `unit.applicationForm`.
  - context: if the link is missing → 404. If `isOpen()` is false (revoked / expired / not
    accepting) → render a friendly "this application is no longer accepting submissions" state
    (still 200 with a closed view, or a dedicated page). Otherwise render `screening/Apply` with
    the unit context (address/label) and the form `fields`.
  - context: tokens are unguessable; do not leak landlord/property internals beyond what an
    applicant needs (unit label + address + the form). See `.docs/features/applicant-flow.md`.
  - done: feature tests — open link renders the apply page with the unit's fields; revoked /
    expired / not-accepting renders the closed state; unknown token 404s.

- [ ] Build the public application page (dynamic form render)
  - context: new `resources/js/pages/screening/Apply.vue`. Do NOT use `AppLayout` (that's the
    authenticated sidebar shell) — create/use a minimal public layout (check
    `resources/js/layouts/auth` for a lightweight pattern) so there's no app chrome. Render each
    field from the schema by `FieldType`: text/long/number/currency/date inputs, single/multi
    choice, boolean checkbox, file inputs, the reference block (name/email/phone/relationship),
    and the consent checkbox. Mobile-first (applicants apply from a phone — see applicant-flow).
  - context: post with Inertia `useForm` (supports file uploads / multipart) to the submit route
    below. Show required markers and inline validation errors.
  - context: activate `inertia-vue-development` + `frontend-design`. Single root element.
  - done: an Inertia/page assertion that the apply page renders the fields; build + vue-tsc clean.

- [ ] Handle application submission (create Application + snapshot + Documents)
  - context: `PublicScreeningController@store` on `POST /screening/{token}` (name
    `screening.store`), outside auth. Re-check `isOpen()` (reject if closed). Validate answers
    **against the unit's current form schema** — required fields present, choice values in
    `options`, files only for `File` fields with sane mime/size limits. Build a
    `FormRequest` that reads the schema to generate rules.
  - context: on success: store each uploaded file on the private `local` disk
    (`storage/app/private`, e.g. under `applications/{ulid}/`), create the `Application`
    (copy the current schema into `form_snapshot`, persist `answers`, applicant contact from the
    identity fields, `status` = New, `submitted_at` = now), and create a `Document` row per file.
    Then redirect to a confirmation page (`screening/Submitted`) — no account, just a thank-you.
  - context: one submission per applicant per link is the v1 assumption (no edit/resubmit) — see
    applicant-flow open questions; don't build resubmit.
  - done: feature tests — a valid multipart submission creates the Application with a
    `form_snapshot`, persists answers, stores uploaded files (use `Storage::fake()`) and Document
    rows, and shows the confirmation; a submission missing a required field is rejected; a
    submission to a closed link is rejected.

### Landlord — applicants (CRUD only)

- [ ] Applicants list page for a unit
  - context: new `ApplicationController@index` (e.g. `units/{unit}/applicants`, name
    `units.applicants.index`, in the auth+verified group). Authorize via the unit policy. List
    the unit's applications with applicant name, submitted date, status (badge), document count.
    Render `screening/applicants/Index.vue`. NO score column (scoring is deferred).
  - context: reuse the table/badge patterns from `properties/Index.vue` / `Show.vue`. Sort by
    `submitted_at` desc.
  - done: a feature test asserting the owning landlord sees their unit's applications and not
    another unit's; non-owner 403.

- [ ] Application detail page (render from snapshot)
  - context: `ApplicationController@show` → `screening/applicants/Show.vue`. Render the submitted
    answers using the application's **`form_snapshot`** (so later form edits don't change history
    — see data-model). Show applicant contact, every field's label + answer, the reference block,
    a list of uploaded documents (with download links — see the download task), and the current
    status. Label the data clearly as **applicant-provided / unverified** (per
    `landlord-dashboard.md` design notes).
  - done: a feature test asserting the detail page renders an application's snapshot answers and
    its documents for the owner; non-owner 403.

- [ ] Update application status + landlord notes
  - context: `ApplicationController@update` — change `status` (validate against
    `ApplicationStatus`) and edit `landlord_notes`. Wire status actions
    (New / Reviewing / Approved / Rejected) + a notes field into `Show.vue`. dwellow never
    auto-decides — this is purely the landlord's manual action.
  - done: a feature test asserting the owner can move an application Reviewing→Approved and save
    notes, and the change persists; non-owner 403.

- [ ] Delete an application (and clean up its documents)
  - context: `ApplicationController@destroy` — delete the application and remove its stored files
    from disk (delete each `Document`'s file via `Storage::disk(...)->delete(...)`, then the rows
    cascade). Confirm in the UI before deleting. Authorize via policy.
  - done: a feature test (with `Storage::fake()`) asserting destroy removes the application,
    its document rows, and the files from disk; non-owner 403.

- [ ] Secure document download for landlords
  - context: `DocumentController@download` (auth+verified) streams a `Document` from the private
    disk only to the owning landlord (authorize via `DocumentPolicy`). Use
    `Storage::disk($document->disk)->download($document->path, $document->original_name)`.
    Never serve these from a public URL.
  - done: a feature test (`Storage::fake()`) asserting the owner downloads the file (200 +
    correct filename) and a different landlord is denied (403).

### Integration & polish

- [ ] Surface applicant activity on the dashboard / properties
  - context: on the dashboard (`DashboardController` + its Vue page) and/or the properties index,
    surface a lightweight applicant signal — e.g. a count of new applications across the
    landlord's units and a link straight to the busiest unit's applicants list. Reuse existing
    card/stat patterns. Keep it read-only.
  - done: a feature test asserting the dashboard payload includes the applicant count for the
    authenticated landlord's units.

- [ ] (Optional, later) Lightweight email verification before submission
  - context: per `.docs/features/applicant-flow.md`, gate submission behind a one-time email code
    so applications are attributable and link spam is deterred. Send a code to the applicant's
    email, verify it, then allow the `Application` to be created. Build only after the core CRUD
    flow above is solid; this is intentionally last and optional for this milestone.
  - done: a feature test (`Notification::fake()` / `Mail::fake()`) asserting a code is sent and an
    unverified submission is blocked until the code is confirmed.

## Research summary — what landlords screen for (informs the default form)

dwellow is **documents-only** in v1: applicants *submit* their own information and documents —
dwellow never pulls numbers, runs a credit/background check, or contacts a bureau (no bureau
integrations — see ADR 0002). All data is **applicant-provided and labeled unverified**. This is
a **Canadian** product, so the framing below follows Canadian norms (not US FCRA/Fair-Housing).
Standard rental applications here collect, and applicants typically provide documents for, five
areas:

- **Identity & contact** — legal name, date of birth, phone, email, government-issued **photo ID**.
  Do NOT collect a SIN (privacy guidance discourages it).
- **Residence history** — current address, monthly rent, move-in date, reason for leaving, and a
  prior/current **landlord reference** (name + contact).
- **Employment & income** — employer, job title, employment type, **gross monthly income**, and
  proof via **recent pay stubs**, an employment letter, or a bank statement. (Affordability rule
  of thumb: income ≈ 3× rent — a future scoring criterion, not built here.) Applicants may also
  attach a credit report they pulled themselves (Equifax/TransUnion Canada) — optional, not run
  by dwellow.
- **Occupancy** — desired move-in date, number of occupants, pets, smoking.
- **Consent** — a privacy/consent acknowledgement that the applicant agrees to dwellow/the
  landlord **using and verifying the submitted information and contacting their references**.
  Under Canadian privacy law (PIPEDA and provincial equivalents) personal information should be
  collected with consent for a stated purpose; provincial **human-rights codes** also prohibit
  discriminatory screening, so the same criteria must be applied to every applicant. A required
  consent field belongs on every form even in a documents-only v1.

Note: residential tenancies are regulated **provincially** in Canada (e.g. Ontario's RTA / LTB),
so specifics vary by province — keep the default form general and let landlords customize.

Sources:
- [Avail — Guide to standard rental application forms](https://www.avail.com/education/articles/guide-to-standard-rental-application-forms)
- [MySmartMove — Rental documents to keep on file](https://www.mysmartmove.com/blog/rental-documents-landlord-forms)
- [LeaseRunner — Documents needed for apartment rental](https://www.leaserunner.com/blog/documents-needed-for-apartment-rental)
- [iPropertyManagement — Rental application form template](https://ipropertymanagement.com/templates/rental-application-form)

## Done — previous milestone (roles, auth, branded email, Filament, landing page)

Completed and in git history (`git log`): Admin role + Filament gating & user-role management;
role selection at signup; email verification enforcement + branded verification/welcome emails;
Filament Property resource + units relation manager; units on the property detail page; landing
page redesign + SEO. See commits up to `33d6ca6`.
