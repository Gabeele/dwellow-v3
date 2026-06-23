# Scope

## In scope for v1 (screening only)

- Public marketing site + landlord registration/auth.
- Landlord manages **properties** and **units**.
- **Dynamic application form builder** — landlord customizes fields per unit.
- **Shareable application link** per unit.
- **Applicant submission** flow — link-only, no account, with document uploads.
- **Automated reference outreach** — dwellow emails references a short form.
- **AI scoring job** — background job parses a submission and produces a score +
  flags against the landlord's criteria.
- **Landlord dashboard** — compare/contrast applicants with scores and statuses.
- **Scorecard criteria** — sensible defaults, customizable per property.

## Explicitly deferred (known boundaries, not accidents)

- **Third-party bureau checks** (credit, criminal, eviction records via TransUnion/
  Experian/Checkr/etc.). v1 uses **tenant-provided documents only**.
  See [ADR 0002](../decisions/0002-no-bureau-integrations.md).
- **Applicant accounts / reusable profiles** across properties.
- **Rent collection, leases/e-sign, maintenance, accounting** — the north-star
  lifecycle. See [roadmap](../roadmap.md).
- **Public listing pages / a listing marketplace.** dwellow is not a listing site;
  landlords bring their own traffic (Zillow, Facebook, signage) and share a link.
- **Payments** — landlord subscription billing is intended but not part of the
  initial build. See [ADR 0005](../decisions/0005-landlord-subscription.md).

## Non-goals

- Becoming a regulated Consumer Reporting Agency (CRA) in v1.
- Serving professional property-management companies (different role/scale model).
