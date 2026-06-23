# Feature: Dynamic Application Form Builder

## Purpose

Let each landlord decide **what they want to see** from applicants for a given unit.
The form is not fixed — it's a customizable schema the landlord shapes.

## Behavior

- Each **unit** has one application **form**.
- The landlord adds/edits/removes/reorders **fields**.
- The form is rendered to applicants from this stored schema (no code change to add a
  field).

### Field types (starting set)

| Type | Example use |
| --- | --- |
| Short text | Full name, current employer |
| Long text | "Tell us about yourself" |
| Number / currency | Monthly income, desired move-in budget |
| Date | Move-in date, employment start |
| Choice (single/multi) | Pets? Smoker? Number of occupants |
| File upload | Pay stubs, ID, bank statements |
| Reference block | Name + email + relationship (drives [reference outreach](./references.md)) |

### Defaults

dwellow ships a sensible **default form** (income, employment, occupancy, references,
document uploads) so a landlord can share a link in seconds, then customize if they want.
This pairs with the default [scorecard criteria](./scoring-engine.md).

## Design notes

- **Form schema is versioned/snapshotted onto each submission.** If a landlord edits
  the form after someone applies, the existing application must still render exactly as
  it was submitted. (See [data model](../domain/data-model.md).)
- Fields can be marked **required** and can map to **scoring inputs** (e.g. an income
  field feeds the rent-to-income criterion).

## Open questions

- Do we let landlords save/reuse a custom form as a template across units? *(Deferred;
  default form covers the common case.)*
- Which fields are "scoring-mapped" vs free-form, and is that mapping landlord-visible?
  Tracked in [open-questions](../open-questions.md).
