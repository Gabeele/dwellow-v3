# Feature: Scoring Engine (AI-Generated Score)

## Purpose

Turn a raw application (form answers + uploaded documents + reference responses) into a
**comparable score and a set of flags** against the landlord's criteria — automatically,
in the background — so the landlord can decide in minutes instead of hours.

See [ADR 0004](../decisions/0004-ai-generated-score.md) for the why behind using AI,
and [ADR 0006](../decisions/0006-score-via-agent-engine.md) for how it's built — the
polymorphic **Agent** engine, holistic-v1 `fit_score`, provider config, and the
deferral of the per-criterion scorecard.

## How it works

1. Applicant submits → an **Application** is created.
2. A queued **scoring job** (`ScoreApplication`) runs:
   - parses structured fields,
   - extracts/reads uploaded documents,
   - incorporates reference responses (or notes their absence),
   - evaluates each **criterion**, computing pass/fail/uncertain + a rationale,
   - produces an overall **score** and **flags**.
3. The result is written back to the Application and surfaced on the
   [dashboard](./landlord-dashboard.md).

## Criteria model

- Criteria come from **dwellow defaults**, **customizable per property**.
  See [ADR 0001-scope] and the criteria-source decision.
- **Auto where possible**: criteria backed by structured fields are computed
  deterministically (e.g. rent-to-income ratio from a reported income field). Criteria
  backed by documents/free text are evaluated by the AI step. Document/reference
  criteria that can't be judged confidently are marked **needs review**, never silently
  passed.

## Guardrails (important)

- **Self-reported data.** Inputs are applicant-provided and unverified. The score must
  be framed as a **screening aid, not a verified decision**, and the UI must say so.
- **Fair-housing safety.** The scoring rubric must **only** consider legally permissible
  criteria (income, references, occupancy, etc.) and must avoid protected-class proxies.
  This is a hard requirement, called out in [open-questions](../open-questions.md).
- **Explainability.** Every score must come with a **per-criterion rationale** so the
  landlord understands *why* — a black-box number isn't trustworthy or defensible.

---

## ⚠️ NEEDS YOUR INPUT: Default criteria & weights

> This is the product's opinion about "what makes a good applicant." It encodes your
> domain knowledge as a landlord — it should be **yours**, not a guess from me.

Please fill in the **default scorecard** below (these become the out-of-the-box
criteria every landlord starts with and can then customize):

| Criterion | Input source | Pass rule (default) | Weight / importance | Hard fail? |
| --- | --- | --- | --- | --- |
| Rent-to-income ratio | income field vs unit rent | e.g. income ≥ 3× rent | _?_ | _?_ |
| Employment | employment field + doc | _?_ | _?_ | _?_ |
| Prior-landlord reference | reference response | _?_ | _?_ | _?_ |
| Eviction disclosure | applicant-disclosed | _?_ | _?_ | _?_ |
| Occupancy vs unit | occupants vs unit limit | _?_ | _?_ | _?_ |
| _add your own…_ | | | | |

Consider:
- **Which criteria are "hard fails"** (auto-reject regardless of score) vs. weighted
  factors? Hard fails are simple and safe but blunt; weighted factors are nuanced but
  can let a red flag slide.
- **How should "no reference response" score** — neutral, or a small penalty? This
  decides whether non-responsive references quietly sink good applicants.
- **What's the score's shape** — a 0–100 number, a letter grade, or just
  Strong/Review/Weak buckets? Simpler buckets are often more honest given unverified data.

Once you fill this in, it drives both the default form fields and the scoring job's rubric.
