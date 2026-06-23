# Fix Plan — Ralph task list

> Format: `- [ ] <task>` for todo, `- [x] <task>` when done, `- [blocked] <task> — reason` if stuck.
> One task = one commit. Most important / most foundational first.
> Context bullets give the agent what it needs; follow `PROMPT.md` for the loop rules and definition of done.

## Milestone: Screening — whole-rental parity, discoverability & field toggles

The screening CRUD milestone is **built** (see "Done" at the bottom + git history): per-unit custom
application forms seeded from a dwellow default, shareable links with an accepting toggle, the public
applicant flow with email-code verification, and the landlord's applicants list / detail / status /
document download. **Do NOT rebuild any of that** — each task below extends or wires existing code.

This milestone closes the gaps that make the built flow look missing or broken to the landlord:

1. **Whole rentals can't be screened at all.** The domain model (`.docs/domain/data-model.md`) says a
   property "groups one or more units" and **"screening happens at the unit level"** — but a property
   with `rental_type = whole` is created with **zero units**, so it has no application form, no link,
   and no applicants surface. Every screening entity is unit-scoped, so whole rentals fall through.
2. **The form builder is unreachable.** `units.form.edit` + `screening/forms/Edit.vue` exist and work,
   but nothing in the UI links to them, so landlords can't find where to configure the fields.
3. **No way to toggle a default requirement off** without deleting the field.

Guardrails (unchanged from the prior milestone — see `.docs/decisions/`):
- **CRUD only.** No AI inferencing / scoring job / automated reference outreach (deferred — ADR 0001,
  `.docs/features/scoring-engine.md`). Do NOT build them.
- **Documents-only, Canadian, applicant-provided/unverified.** dwellow never pulls a credit/background
  bureau (ADR 0002). "Credit report" = a file the applicant uploads themselves, never an integration.
- Use the glossary terms (`.docs/domain/glossary.md`): Property, Unit, Application Form, Application
  Link, Application, Document.

## Tasks

### Whole-rental properties can be screened (root cause — do first)

- [x] Give every whole-rental property a single backing unit
  - done: added `PropertyObserver@created` (attached via `#[ObservedBy]` on `Property`) that
    firstOrCreates one backing `Unit` for `RentalType::Whole` properties only, seeded from the
    property's label/bedrooms/bathrooms/rent_amount/status; `UnitObserver` then provisions the default
    form. Multi-unit creation is untouched; re-fire adds no second unit. Covered by
    `tests/Feature/PropertyObserverTest.php` (3 tests). Full suite green (182).
  - context: the data model treats a whole rental as a property with **one** unit ("screening happens
    at the unit level"). Today `PropertyController@store` (and `StorePropertyRequest`) create the
    property but no unit when `rental_type = RentalType::Whole`. Make whole-rental creation also create
    exactly one backing `Unit` that represents the whole property — prefer a `PropertyObserver`
    (`app/Observers/PropertyObserver.php`, attached via `#[ObservedBy]` on `app/Models/Property.php`)
    so every creation path is covered, mirroring the existing `UnitObserver` pattern.
  - context: seed the backing unit from the property's own rentable attributes (label e.g. the property
    name or "Whole property"; copy `bedrooms`, `bathrooms`, `rent_amount`, `status`). The existing
    `UnitObserver` will then auto-provision its default `ApplicationForm` — don't duplicate that.
  - context: multi-unit properties must be **unaffected** — they keep creating their own units via
    `UnitController`. Only `Whole` properties get the auto backing unit, and only one (guard against a
    second on re-fire, like `UnitObserver` does).
  - done: a feature test asserting creating a `Whole` property yields exactly one unit (with a default
    form), creating a `MultiUnit` property yields zero auto units, and re-saving a whole property does
    not add a second unit.

- [x] Backfill a backing unit for existing whole-rental properties
  - done: added `properties:backfill-backing-units` command (`BackfillWholeRentalUnits`) that finds
    `Whole` properties with `doesntHave('units')` and calls the extracted
    `PropertyObserver@provisionBackingUnit`, so the backfill and live-creation paths share one
    definition; `UnitObserver` provisions the default form. Idempotent (query filter + observer
    `firstOrCreate`). A data migration (`2026_06_23_201315_backfill_backing_units_for_whole_rentals`)
    invokes the command on deploy. Covered by `tests/Feature/BackfillWholeRentalUnitsTest.php` (4 tests:
    unit-less whole gets one unit+form, double-run adds nothing, existing-unit untouched, multi-unit
    skipped). PropertyObserver + backfill tests green (7).
  - context: any `Whole` property created before the observer has no unit. Add a migration (or an
    idempotent artisan command invoked by a migration) that creates the single backing unit + default
    form for each existing `Whole` property that has none. Idempotent — safe to run twice.
  - done: a feature test seeding a unit-less whole property, running the backfill, and asserting it now
    has exactly one unit with a default form; running it again adds nothing.

- [x] Render the screening surface for whole-rental properties on the property page
  - done: `properties/Show.vue` now renders a `Screening` section (`v-else-if="backingUnit"`) that reuses
    `UnitScreeningPanel` for the whole rental's single backing unit (`units[0]`), without the multi-unit
    Units-table chrome. No controller change needed — `PropertyController@show` already eager-loads every
    property's units with `applicationLinks` + applicant counts + `public_url`, and the backing unit is a
    regular `Unit`, so its screening data was already in the payload. Updated the stale "empty units array"
    test in `PropertyShowRedesignTest.php` to assert the backing unit's links + applicant counts (whole
    rentals now have a backing unit); multi-unit show test unchanged. Suite green (3), `vue-tsc` + build clean.
  - context: `properties/Show.vue` only renders units + `UnitScreeningPanel` inside `v-if="isMultiUnit"`.
    For a whole rental, render the **same** `UnitScreeningPanel` for its single backing unit — but as the
    property's own "Screening" section, **without** the multi-unit "Units" table chrome (no per-unit rows,
    add-unit button, etc.). Do not fork the panel; reuse it.
  - context: `PropertyController@show` must eager-load the backing unit's `applicationLinks`
    (+ application counts + `public_url`) and `applicationForm` for whole rentals the same way it does
    for multi-unit, so the panel has its data.
  - done: a feature test asserting the whole-rental show payload includes the backing unit's links and
    applicant counts; multi-unit show is unchanged (regression). `vendor/bin/sail npm run build` +
    `vue-tsc` clean.

### Configure the application form — make it reachable and toggleable

- [ ] Link to the per-unit application-form builder from the screening UI
  - context: the builder exists (`units.form.edit` → `screening/forms/Edit.vue`) but is orphaned —
    `UnitScreeningPanel.vue` links to applicants and creates links but never to the form builder. Add a
    clear "Customize application form" action in `UnitScreeningPanel` (so it appears for both a unit and a
    whole-rental backing unit) using the Wayfinder-generated action for `units.form.edit`. Make it obvious
    the form is specific to that unit/property.
  - context: confirm a landlord who has never opened the builder still lands on the seeded default form
    (existing `firstOrCreate` behaviour) — don't break it.
  - done: a feature/inertia test asserting the screening UI exposes a working route to `units.form.edit`;
    `vue-tsc` + build clean.

- [ ] Add a per-field enable/disable toggle to the form builder
  - context: a landlord should be able to switch a dwellow-default requirement (e.g. pay stubs, photo ID,
    a reference) **on or off per unit without deleting it**, then turn it back on later. Add an `enabled`
    boolean to the field shape (default `true`) across: `App\Screening\DefaultApplicationForm::fields()`
    (every field `enabled: true`), `App\Enums\FieldType` consumers, `UpdateApplicationFormRequest`
    validation, and the `screening/forms/Edit.vue` UI (a visible include/exclude toggle per field card).
  - context: a **disabled** field must NOT render on the public `screening/Apply.vue` form and must NOT be
    required or validated in `StoreApplicationRequest` (the schema-driven rules must skip disabled fields).
    The `form_snapshot` saved on submission should reflect only the fields that were active at submit time.
  - context: keep backward compatibility — treat a field with no `enabled` key as enabled.
  - done: feature tests — a disabled field is absent from the public apply payload and a submission
    omitting it still succeeds; toggling a field off then on persists through the builder; existing forms
    (no `enabled` key) still render and submit. `vue-tsc` + build clean.

### Verification — confirm, don't rebuild

- [ ] Audit the applicant email-verification flow end-to-end (do not rebuild)
  - context: this is **already implemented and tested** — `App\Screening\EmailVerification`,
    `ApplicationVerificationCodeNotification`, `emails.application-code`, the verify/store gating in
    `PublicScreeningController`, the Send/Resend-code UI in `Apply.vue`, and
    `tests/Feature/ApplicationEmailVerificationTest.php`. Mailpit is wired for local dev. Do not duplicate it.
  - context: confirm the flow works through a **whole-rental** link too (after the backing-unit tasks):
    request a code → it's emailed → a valid code allows submission → a wrong/expired code is rejected.
    Only if you find a real defect (verify step skippable, code field not enforced, email won't render,
    flow unreachable from a valid link) fix it and add a covering test; otherwise leave the code untouched.
  - done: `vendor/bin/sail artisan test --compact --filter=ApplicationEmailVerification` passes; if a
    whole-rental path was added, a test covers it. No duplicate verification logic introduced.

## Design & domain references

Read before touching a task: `.docs/features/application-form-builder.md`, `applicant-flow.md`,
`landlord-dashboard.md`, `.docs/domain/data-model.md`, `.docs/domain/glossary.md`, and the ADRs in
`.docs/decisions/`. Use the existing components (`UnitScreeningPanel`, `PageHeader`, `DataTable`,
`MetricCard`, `ui/*`) and design language already in `properties/Show.vue`. Activate the
`inertia-vue-development`, `tailwindcss-development`, `frontend-design`, and `pest-testing` skills.

## Done — previous milestone (screening CRUD)

Completed and in git history: `FieldType`/`ApplicationStatus` enums + the dwellow `DefaultApplicationForm`;
`ApplicationForm`/`ApplicationLink`/`Application`/`Document` models, migrations, factories, policies;
per-unit auto-provisioned default form (`UnitObserver`); the form-builder controller + page; link
create/toggle/revoke; the public applicant flow (dynamic render, submission, snapshot, documents);
applicants list/detail/status/notes/delete; secure document download; dashboard applicant signal; and
applicant email-code verification. The whole "Tenant screening — custom application forms" milestone is
checked off — see the prior `ralph.md` revision and `git log`.
