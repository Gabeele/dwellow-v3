# Open Questions

Unresolved decisions, tracked openly. Resolve into an ADR or a feature doc when
decided. Grouped by area.

## Scoring & criteria (highest priority)
- [ ] **Default criteria, thresholds, and weights** — needs your domain input. See the
      table in [scoring engine](./features/scoring-engine.md).
- [ ] **Score shape** — 0–100 number, letter grade, or Strong/Review/Weak buckets?
- [ ] **Hard-fail vs weighted** — which criteria auto-reject vs. just lower the score?
- [ ] **"No reference response" handling** — neutral, or a penalty?
- [ ] **Fair-housing rubric review** — confirm only permissible criteria; no
      protected-class proxies. (Legal review before launch.)
- [ ] **LLM model/provider** for the scoring job (default: a current Claude model).

## Form & application
- [ ] Reusable custom-form templates across units? (Deferred — default form covers most.)
- [ ] Which fields are "scoring-mapped" vs free-form, and is that visible to landlords?
- [ ] Can an applicant edit/resubmit after submitting? (Assumed no.)

## Applicant flow
- [ ] Verification channel: email only, or email **and** SMS?
- [ ] Application link expiry/revocation defaults.

## References
- [ ] Reminder cadence and no-response timeout window.
- [ ] Question sets per reference type (prior-landlord vs employer vs personal).

## Data & compliance
- [ ] **Document retention policy** — how long do we keep pay stubs/IDs, and deletion
      flow? (Sensitive PII.)
- [ ] Applicant data rights (access/delete) — what do we commit to?
- [ ] Decision notifications to applicants — do we auto-notify on approve/reject, and
      with what wording? (UX + legal.)

## Accounts & roles
- [ ] Team members / co-owners on a landlord account in v1? (Assumed no.)

## Monetization
- [ ] Subscription tiers, unit/application limits, and free-tier boundary.
- [ ] Billing provider (likely Stripe via Cashier) and when to activate.
