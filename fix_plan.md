# Fix Plan — Ralph task list

> Format: `- [ ] <task>` for todo, `- [x] <task>` when done, `- [blocked] <task> — reason` if stuck.
> One task = one commit. Most important / most foundational first.
> Context bullets give the agent what it needs; follow `PROMPT.md` for the loop rules and definition of done.

## Tasks

### Roles

- [x] Add an `Admin` case to the `Role` enum
  - context: `app/Enums/Role.php` currently has only `Landlord` and `Tenant`. Add `case Admin = 'admin';` and its `label()` arm ("Admin").
  - context: Admin must NOT be self-assignable at signup. Check `app/Concerns/RoleValidationRules.php` and ensure the registration `roleRules()` only allows `landlord`/`tenant` (the `Role` enum is the full set, but registration is restricted).
  - done: unit test covering `Role::Admin->label()` and a feature/validation test asserting a registration request with `roles: ['admin']` is rejected.
  - note: Added `Role::Admin` + label; restricted registration `roleRules()` via `Rule::enum(Role::class)->only([Landlord, Tenant])`. Added `tests/Unit/RoleTest.php` and an admin-rejection case in `RoleAssignmentTest`. All green.

- [x] Gate Filament panel access by the `Admin` role (keep the email allowlist as a fallback)
  - context: `app/Models/User.php` `canAccessPanel()` currently only checks `config('admin.emails')`. Change it to allow access when the user `isAdmin()` OR is on the allowlist.
  - context: add an `isAdmin(): bool` helper to `app/Concerns/HasRoles.php` (mirrors existing `isLandlord()`/`isTenant()`).
  - done: feature test — an Admin-role user can access the panel; a Landlord/Tenant not on the allowlist cannot; an allowlisted user still can.
  - note: Added `isAdmin()` to HasRoles, `canAccessPanel()` now `isAdmin() || allowlist`, added `admin()` UserFactory state, and two cases (admin allowed / landlord+tenant denied) in PanelAccessTest. Existing allowlist test still green.

- [x] Show and manage user roles in the Filament User resource
  - context: resource lives in `app/Filament/Resources/Users/`. Add roles to `Tables/UsersTable.php` (a badge column listing the user's roles) and `Schemas/UserForm.php` (a multi-select of `Role` cases) so an admin can assign/remove roles. Roles are stored via the `role_user` pivot (use the `HasRoles` trait's `assignRole`/`removeRole`, or sync on save).
  - done: feature test asserting an admin can save a user with selected roles and the pivot reflects it.
  - note: Added a `roles.role` badge column, a non-dehydrated `roles` multi-select, and a `SyncsUserRoles` page trait that reads `$this->data['roles']` and calls a new `HasRoles::syncRoles()`. EditUser pre-fills via `mutateFormDataBeforeFill`. Tests in `Filament/UserRolesTest`. Also (per user request) `UserSeeder` now assigns `Role::Admin` to allowlisted admins — covered by `UserSeederTest`.

### Signup

- [x] Let users choose their role (Landlord or Tenant) on the registration form
  - context: backend already handles `roles[]` in `app/Actions/Fortify/CreateNewUser.php` (defaults to Tenant). The frontend form is `resources/js/pages/auth/Register.vue`. Add a role selector (Landlord/Tenant) that posts `roles`.
  - context: reuse existing UI components (check `resources/js/components/ui/select` and how other auth pages build forms). Do not allow Admin here.
  - done: an Inertia/feature test posting registration with a chosen role creates the user with that role on the pivot.
  - note: The role selector already exists in `Register.vue` (radio cards posting `roles[]`, no Admin). Per the documented v1 scope (product memo: "v1 is landlord-only"), Tenant is intentionally shown but disabled ("coming soon") — NOT enabling it, as that would contradict the product decision. Added a focused registration-flow test in `Auth/RegistrationTest` asserting registering with `roles: ['landlord']` lands the landlord role on the pivot.

### Email verification + branded emails

- [x] Enforce email verification on the User model
  - context: Fortify's `Features::emailVerification()` is already enabled in `config/fortify.php`, but `app/Models/User.php` does NOT implement `MustVerifyEmail` (the import is commented out), so it isn't enforced. Implement `Illuminate\Contracts\Auth\MustVerifyEmail` on `User`.
  - context: confirm verified-only routes use the `verified` middleware; check `routes/` and the dashboard route. Mail goes to Mailcatcher (current mail driver) — no real delivery.
  - done: feature test — an unverified user hitting a `verified`-protected route is redirected to the verification notice; a verified user passes.
  - note: `User` now implements `MustVerifyEmail` (the `verified` middleware was already on the dashboard + settings routes but was a no-op without the interface). Added two tests to `Auth/EmailVerificationTest`: unverified → redirect to `verification.notice`, verified → 200. Full suite (91 tests) stays green.

- [x] Send a branded email-verification email on signup
  - context: override the default verification notification. Add a custom notification (e.g. `app/Notifications/VerifyEmailNotification.php` extending Fortify/Laravel's `VerifyEmail`) and wire it via `User::sendEmailVerificationNotification()` or `VerifyEmail::toMailUsing(...)` in a service provider. Use a branded Markdown mailable (dwellow name/logo/colors) with the signed verification URL.
  - context: keep the signed URL generation from the base class — only customize the presentation.
  - done: feature test using `Notification::fake()` asserting the custom verification notification is sent to a newly registered user.
  - note: Added `VerifyEmailNotification` extending Laravel's `VerifyEmail` (keeps signed URL gen, overrides `buildMailMessage`) rendering a branded `resources/views/emails/verify-email.blade.php` Markdown mailable (dwellow name, green success button). Wired via `User::sendEmailVerificationNotification()`. Added a registration→notification test to `EmailVerificationTest`, and updated the existing `VerificationNotificationTest` assertion to the new class. All 96 green.

- [x] Send a branded welcome email after a user verifies their email
  - context: listen for `Illuminate\Auth\Events\Verified` (register the listener in `app/Providers/`), and send a branded `WelcomeMail` Mailable (Markdown). Should NOT send on registration — only after verification.
  - done: feature test using `Mail::fake()`/`Notification::fake()` asserting the welcome email is sent when the `Verified` event fires, and not before.
  - note: Added `WelcomeMail` Markdown mailable (branded `emails.welcome` view, dwellow copy + dashboard button) and a `SendWelcomeEmail` listener type-hinting `Verified` (auto-discovered, same pattern as `RecordSentEmail` — no provider wiring needed). Added `Auth/WelcomeEmailTest`: nothing sent at registration, `WelcomeMail` sent to the user after the verification link fires `Verified`. Full suite (98) green.

### Filament — properties

- [x] Add a Filament resource for viewing properties
  - context: no Property resource exists yet (only `app/Filament/Resources/Users/`). Generate/create a `PropertyResource` for the `Property` model with a list table (name/address/city, type, rental_type, status, landlord) and a view/detail page. Read-focused is fine; mirror the structure of the existing Users resource.
  - context: enums for columns are `App\Enums\PropertyType`, `RentalType`, `OccupancyStatus`.
  - done: feature test asserting an admin can load the property list page and it renders an existing property (use `Property` factory).
  - note: Added read-only `PropertyResource` (mirrors `SentEmailResource`: `canCreate`/`canEdit` false, index+view pages) with `PropertiesTable` (name/address/city + type/rental_type/status badges via `->formatStateUsing(label())` + landlord) and `PropertyInfolist`. The existing `PropertyPolicy` is landlord/ownership-scoped and denied admins (403), so overrode `canViewAny()`/`canView()` to `true` on the resource — panel access is already admin-gated by `canAccessPanel()`. Added `Filament/PropertyResourceTest` (list + view). Full suite (100) green.

- [x] Show a property's units in Filament
  - context: `Property hasMany Unit` (see `app/Models/Property.php`). Add a Units relation manager to the `PropertyResource` so an admin can see the units under a property (label, bedrooms, bathrooms, rent_amount, status).
  - done: feature test asserting the relation manager lists a property's units (use `Property` + `Unit` factories).
  - note: Generated `UnitsRelationManager` and made it read-only to match the read-only `PropertyResource` (removed create/edit/dissociate/delete actions — only `ViewAction`). Table + infolist show label/bedrooms/bathrooms/rent_amount (money usd)/status (badge via `label()`). Registered via `PropertyResource::getRelations()` (auto-renders on the View page). Added `Filament/PropertyUnitsRelationManagerTest` (lists owned units, excludes another property's unit). All 12 Filament tests green.

### Units on the tenant/landlord app

- [x] Show associated units on the property detail page
  - context: a property is either rented "whole" (single house) or as multiple independent units — this is the `rental_type` field (`App\Enums\RentalType`). On the Inertia property show page (`resources/js/pages/properties/Show.vue`, served by `app/Http/Controllers/PropertyController.php@show`), when `rental_type` is multi-unit, list the associated units with their label/bedrooms/bathrooms/rent/status; when "whole", show the property's own rentable details.
  - context: eager-load `units` in the controller's `show` method. Reuse existing display patterns from the Index/Show pages.
  - done: feature test asserting the show response includes the property's units for a multi-unit property.
  - note: Already fully implemented by prior redesign work — `PropertyController@show` eager-loads `units` (`$property->load('units')`), and `Show.vue` branches on `rental_type`: a units DataTable (label/bedrooms/bathrooms/rent/status) for multi-unit, and the property's own rentable metrics for whole rentals. `PropertyShowRedesignTest` already covers the done criterion (multi-unit asserts `property.units` has 2; whole-rental asserts the units array is present). Verified green (2 tests, 21 assertions). No code change needed.

### Landing page

- [x] Redesign the landing page content and layout
  - context: `resources/js/pages/Welcome.vue`. Build an eye-catching marketing landing page: hero explaining what dwellow does now (tenant screening for small landlords — see the product memo/`CONTEXT.md`), key features/benefits, and a roadmap/timeline section for future plans. Make it look polished using the existing Tailwind v4 setup and UI components. Activate the `tailwindcss-development` and `frontend-design` skills.
  - done: feature test asserting the `/` route renders the Welcome page (Inertia component assertion) and key copy is present.
  - note: Rebuilt `Welcome.vue` within the app's existing design tokens (Geist + income-lens `success` green as the signature accent; kept the AI applicant-score hero card as the memorable element). Added a real 5-step "How it works" screening sequence (numbered, since the flow is ordered), 3 benefit cards (docs-not-bureau, references-handled, one-Score), a Now/Next/Later roadmap (from `.docs/roadmap.md`, "Now" highlighted), and a closing CTA. Drove `steps`/`features`/`roadmap` from Inertia props (set in the `/` route closure) so copy is assertable. Extended `LandingTest` to assert prop counts + key copy. Pint clean, ESLint clean, vue-tsc clean, `npm run build` green, 2 tests/24 assertions pass.

- [ ] Add SEO support to the landing page
  - context: add proper `<title>`, meta description, Open Graph / Twitter card tags, and JSON-LD structured data (Organization/SoftwareApplication) for the landing page. Use Inertia's `<Head>` (and/or server-shared meta). Ensure a sensible canonical URL.
  - done: feature test (or page assertion) confirming the title/meta description are present in the rendered `/` response.

## Done

<!-- completed tasks get checked off above and noted by the loop -->
