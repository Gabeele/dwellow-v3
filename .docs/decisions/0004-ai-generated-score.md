# 0004 — Scoring is an AI background job

**Status:** Accepted

## Context

The core value is turning a messy, partly free-form, partly document-based application
into a **fast, comparable judgment** that still catches red flags. Pure rule-based
scoring can't read a pay stub PDF or interpret a reference's prose; pure manual review
is exactly the slow process we're replacing.

## Decision

On submission, a **queued background job** (`ScoreApplication`) processes each
application and produces a **score + per-criterion outcomes with rationale**. The job:

- computes deterministic criteria from structured fields (e.g. rent-to-income),
- uses an **LLM** to read documents/free text and reference responses,
- marks anything it can't judge confidently as **needs review** (never silently
  passes),
- writes results back for the dashboard.

The work is asynchronous so the applicant gets an instant confirmation and the landlord
sees results shortly after.

## Consequences

- **Pro:** Handles unstructured inputs (documents, prose references) a rule engine
  can't.
- **Pro:** Async keeps the submit UX instant and lets scoring retry on failure.
- **Con:** Adds an LLM dependency, cost per application, and prompt/rubric maintenance.
- **Hard requirements:**
  - **Explainability** — every score ships with per-criterion reasoning.
  - **Fair-housing safety** — the rubric considers only permissible criteria; no
    protected-class proxies.
  - **Unverified-data framing** — outputs are a screening aid, not proof.

## Open

- Which model/provider, and the exact rubric, are TBD — default to a current Claude
  model. The **default criteria/weights** need the landlord's domain input (see
  [scoring engine](../features/scoring-engine.md)).
