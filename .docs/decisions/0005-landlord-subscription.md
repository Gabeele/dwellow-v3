# 0005 — Monetize via landlord subscription

**Status:** Proposed (intent documented; not built in v1)

## Context

dwellow needs a revenue model. Options considered: free-for-now, landlord
subscription, per-application fee, and applicant-paid application fee.

## Decision

The intended model is a **landlord subscription** (recurring fee, e.g. per active
property or a flat tier). Billing is **not part of the initial build** — v1 may run
free to seed adoption — but the product is designed with subscription in mind.

## Why subscription over alternatives

- **vs per-application fee:** screening volume is lumpy for small landlords;
  predictable recurring revenue is healthier and simpler to reason about.
- **vs applicant-paid fee:** charging applicants adds payment friction on the wedge and
  invites fair-housing/fee-regulation complexity.
- **vs free forever:** subscription aligns with the north-star lifecycle product
  landlords will pay to run their rentals on.

## Consequences

- Pricing tiers, limits (units/applications), and the free tier boundary are **open
  questions** — see [open-questions](../open-questions.md).
- Adds a billing integration (e.g. Stripe/Cashier) when activated — a later workstream.
- Revisit if growth strategy shifts toward tenant-first/network effects.
