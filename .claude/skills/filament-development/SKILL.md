---
name: filament-development
description: "ACTIVATE when building or changing dwellow-v3's Filament v5 admin panel — resources, forms/schemas, tables, infolists, actions, relation managers, or pages under app/Filament. Covers the split-resource layout this repo uses and how to test Filament with Livewire. Do NOT activate for the applicant-facing Inertia UI (use inertia-vue-development)."
license: MIT
metadata:
  author: gavin
---

# Filament Development (dwellow-v3)

Filament **v5** powers the internal/admin panel (the landlord-facing applicant flow is Inertia — different stack). Filament is server-driven UI in PHP on Livewire + Alpine + Tailwind. **Always `search-docs` for the v5 pattern before using a component** — v5 has breaking changes from v4.

## This repo's resource layout (mirror it exactly)
Resources are **split** into sub-namespaces, not one monolithic class. Study a sibling before creating anything — e.g. `app/Filament/Resources/Applications/`:
```
Resources/<Name>/
  <Name>Resource.php              the resource shell (model, nav, pages registration)
  Schemas/<Name>Form.php          form schema (create/edit)
  Schemas/<Name>Infolist.php      read-only infolist (view page)
  Tables/<Name>Table.php          table columns/filters/actions
  Pages/{List,Create,Edit,View}<Name>.php
  RelationManagers/…              related records (e.g. DocumentsRelationManager)
  Concerns/…                      shared traits (e.g. SyncsUserRoles)
```
Create scaffolding with the Filament artisan generators (`vendor/bin/sail artisan make:filament-* --no-interaction`; discover them via `list-artisan-commands`), then move logic into the split classes to match the existing shape.

## Namespaces (v5 — common mistakes)
- Form fields (`TextInput`, `Select`, …): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, …): `Filament\Infolists\Components\`
- Layout (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`): `Filament\Schemas\Components\Utilities\`
- Actions: `Filament\Actions\` (not `Filament\Tables\Actions\`)
- Icons: `Filament\Support\Icons\Heroicon` enum

## Patterns
- Initialize components with static `make()`; most config methods accept a `Closure` for dynamic values.
- `Get $get` reads sibling field values for conditional logic (`->visible(fn (Get $get) => …)`), pair with `->live()`.
- `->state(fn (Record $r) => …)` computes derived table columns.
- Actions encapsulate a button + optional modal form + `->action(fn (array $data, Model $record) => …)`.
- **File fields are `private` visibility by default in v5** — applicant documents are sensitive and belong on the private disk; do not switch to public. Downloads go through a gated action (see `app/Filament/Actions/DownloadDocumentAction.php`), not a public URL.
- Panel access is gated by `User::canAccessPanel()` / the admin allowlist — respect it; never widen access to satisfy a test.

## Testing (Pest + Livewire)
Authenticate first, then drive with `livewire()` / `Livewire::test()`:
- Tables: `->assertCanSeeTableRecords($records)`, `->searchTable(…)`, `->assertCanNotSeeTableRecords(…)`.
- Create/edit: `->fillForm([...])->call('create')->assertNotified()->assertRedirect()` + `assertDatabaseHas`.
- Validation: `->call('create')->assertHasFormErrors(['name' => 'required'])`.
- Actions: `->callAction(TestAction::make('promote')->table($record), [...])->assertNotified()`.

## Definition of done
`vendor/bin/sail artisan test --compact --filter=<Resource>` green (a Livewire test for the change), `vendor/bin/sail bin pint --dirty --format agent` clean, and the resource follows the split layout above.
