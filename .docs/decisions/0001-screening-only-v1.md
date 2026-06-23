# 0001 — v1 is tenant screening only

**Status:** Accepted

## Context

dwellow's north star is a full rental-lifecycle OS for small landlords (screening →
lease → rent → maintenance → accounting). Building all of that at once is slow and
unfocused. We need a single wedge that earns adoption.

## Decision

v1 does **tenant screening only**. Everything else in the lifecycle is explicitly
deferred. Screening is the front door.

## Why screening is the wedge

- It's the **highest-anxiety moment** for a small landlord — the wrong tenant is
  expensive.
- It's painful and manual today (scattered docs, skipped reference calls, inconsistent
  comparison).
- It's a natural acquisition point: landlords come to dwellow at the start of a
  tenancy, before they need anything else.

## Consequences

- Roles stay simple (landlord + account-less applicant + touch-only reference).
- No listing site, no payments-to-tenants, no lease engine in v1.
- The product must still feel complete *as a screening tool*, not like a stub.
- Expansion is intentional and sequenced — see [roadmap](../roadmap.md).
