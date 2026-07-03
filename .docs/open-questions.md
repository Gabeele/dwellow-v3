# Open Questions

Unresolved decisions, tracked openly. Resolve into an ADR or a feature doc when
decided. Grouped by area.

## Scoring & criteria (highest priority)

The **Score** milestone shipped a **holistic v1** Score via the polymorphic Agent engine —
see [ADR 0006](./decisions/0006-score-via-agent-engine.md). That resolves the shape and
provider questions and **defers** the per-criterion scorecard wholesale (v1 is fully
LLM-holistic, no deterministic criteria). Marked below.

- [x] **Score shape** — **Resolved:** a 0–100 `fit_score` (holistic), shipped with a
      rationale, neutral summary, Flags, and strengths. Letter grades / buckets not used.
      ([ADR 0006](./decisions/0006-score-via-agent-engine.md).)
- [x] **LLM model/provider** for the scoring job — **Resolved:** config-driven default
      (`config('ai.default')`) — **Ollama** local, **Anthropic** prod — with **no
      auto-fallback**. ([ADR 0006](./decisions/0006-score-via-agent-engine.md),
      [ADR 0005](./decisions/0005-landlord-subscription.md).)
- [ ] **Default criteria, thresholds, and weights** — _Deferred (v1 is holistic):_ no
      per-criterion scorecard ships in v1; blocked on the landlord default-criteria table.
      See the table in [scoring engine](./features/scoring-engine.md).
- [ ] **Hard-fail vs weighted** — _Deferred (v1 is holistic):_ no per-criterion engine, so
      nothing auto-rejects; the holistic Score never decides for the landlord.
- [ ] **"No reference response" handling** — _Deferred:_ part of the deferred per-criterion
      scorecard (the holistic Score weighs reference presence qualitatively).
- [ ] **Fair-housing rubric review** — confirm only permissible criteria; no
      protected-class proxies. **Still open:** the prompt + UI already enforce
      permissible-only factors per [ADR 0004](./decisions/0004-ai-generated-score.md), but
      **legal review before launch** is still required.

> **Also deferred (scoring v1, out of scope this plan):** the per-unit
> **Scorecard/Criterion/CriterionResult** engine and **per-criterion rationale**;
> **deterministic** criteria computation; a manual **re-score** button; application-list
> **sort/filter by score**; **OCR** for image-only documents; **automatic provider
> fallback**; a **normalized flags table**.

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
      flow? (Sensitive PII.) _Deferred (out of scope of the Score plan)._
- [ ] Applicant data rights (access/delete) — what do we commit to?
- [ ] Decision notifications to applicants — do we auto-notify on approve/reject, and
      with what wording? (UX + legal.)

## Accounts & roles
- [ ] Team members / co-owners on a landlord account in v1? (Assumed no.)

## Monetization
- [ ] Subscription tiers, unit/application limits, and free-tier boundary.
- [ ] Billing provider (likely Stripe via Cashier) and when to activate.
