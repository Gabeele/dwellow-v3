# 0003 — Applicants apply via link, no account

**Status:** Accepted

## Context

Applicants are people the landlord has already met and wants to screen. The faster they
can apply (ideally from a phone), the more complete the funnel. Requiring account
creation adds friction at exactly the wrong moment.

## Decision

Applicants apply directly from a **shared link** with **no account/password**. Identity
is established with **lightweight verification** (one-time email/SMS code) so
submissions are attributable. The application belongs to the unit's link, not to a
reusable applicant profile.

## Consequences

- **Pro:** Lowest possible friction; fits the low-effort small-landlord audience.
- **Pro:** No applicant account system to build/maintain in v1.
- **Con:** **No reusable profile** — applicants re-enter info for a different unit.
- **Con:** Need robust **link integrity**: per-unit, revocable, expirable, multi-use.
- **Con:** "Save my application" / status portal for applicants is limited to
  email/SMS notifications, not a logged-in dashboard.

## Supersession trigger

If we later pursue a **tenant-first / network-effect** strategy, reusable applicant
accounts become valuable and this decision should be revisited with a new ADR.
