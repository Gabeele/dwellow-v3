# Feature: Landlord Onboarding

## Purpose

Get a landlord from the public site to "ready to share an application link" with as
little friction as possible.

## Flow

1. **Discover** — public marketing site explains the screening value prop.
2. **Register** — landlord creates an account (email/password via Fortify; the
   starter kit already provides auth).
3. **Add a Property** — an address-level record (e.g. "123 Oak St").
4. **Add a Unit** — a rentable space within a property (e.g. "Apt 2B", or the whole
   house). A property has one or more units.
5. **Set up screening for a unit** — build the [application form](./application-form-builder.md)
   and (optionally) adjust the [scorecard criteria](./scoring-engine.md).
6. **Share the link** — landlord copies the unit's application link / QR and sends it
   to a prospective tenant.

## Concepts

- **Property** — the building/address. Owned by one landlord.
- **Unit** — the thing being rented and screened for. Each unit has its own form,
  link, criteria, and applicant pool.

> Why property → unit (not just "listing"): a duplex or multi-unit owner screens per
> unit, but groups them under one address. Keeps the model honest for the small
> multi-unit landlord without becoming a listing site.

## Open questions

- Can a landlord have team members / co-owners in v1? **Assumed no** — single landlord
  per account. Tracked in [open-questions](../open-questions.md).
