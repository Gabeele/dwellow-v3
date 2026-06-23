# Roadmap

dwellow leads with screening and expands into the small landlord's full rental
lifecycle. Sequencing is deliberate (see [ADR 0001](./decisions/0001-screening-only-v1.md)).

## Now — v1: Tenant Screening

The complete screening flow:
landlord onboarding → custom application form → shared link → applicant submission →
automated references → AI scoring → compare/contrast dashboard.
Monetization intent: [landlord subscription](./decisions/0005-landlord-subscription.md)
(not yet built).

## Next — deepen screening

Make the wedge best-in-class before broadening:

- Reusable form templates across units.
- Portfolio-wide applicant overview.
- Optional **verified** checks (real bureau integrations) — a separate compliance step
  that revisits [ADR 0002](./decisions/0002-no-bureau-integrations.md).
- Activate subscription billing.

## North star — full rental lifecycle OS

Become the all-in-one tool a small landlord runs their rentals on. Screening is the
front door to:

1. **Lease & onboarding** — convert an approved applicant into a signed, onboarded
   tenant (e-sign, lease docs).
2. **Rent collection** — online payments, reminders, late fees.
3. **Maintenance** — tenant requests, triage, tracking.
4. **Accounting** — per-property income/expense, tax-ready reports.

Each is a future workstream with its own scope and ADRs. The screening relationship
(landlord ↔ applicant) seeds the data the rest of the lifecycle builds on.

> Guiding rule: don't broaden until screening is genuinely loved. A shallow all-in-one
> loses to a great single-purpose tool.
