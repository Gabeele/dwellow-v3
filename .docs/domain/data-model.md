# Data Model (Conceptual)

This is the **product-level** model — the entities and relationships, not migrations.
Implementation (tables, columns, types) lives in `database/migrations`.

## Entities & relationships

```
User (Landlord)
  └─1:N─ Property
            └─1:N─ Unit
                     ├─1:1─ ApplicationForm (current schema)
                     ├─1:1─ Scorecard ──1:N── Criterion        (deferred — see ADR 0006)
                     ├─1:N─ ApplicationLink
                     └─1:N─ Application
                               ├─ form_snapshot (the schema as submitted)
                               ├─1:N─ Document (uploads)
                               ├─1:N─ Reference ──1:0..1── ReferenceResponse
                               ├─1:N─ Agent (morphTo analyzable; one per type)
                               └─1:1─ Score ──belongsTo── Agent (type=score)

Agent (polymorphic engine)
  └─ morphTo analyzable  (e.g. Application; future: maintenance requests, …)
```

## Entity notes

- **User** — reuses the starter kit's `User` model; represents the landlord in v1.
- **Property** → **Unit** — a property groups one or more units. Screening happens at
  the unit level.
- **ApplicationForm** — the unit's current customizable field schema (stored as
  structured JSON). Editable anytime.
- **Application** — carries a **`form_snapshot`** so a submission always renders as it
  was when submitted, even if the form is later edited.
- **ApplicationLink** — per-unit shareable link; revocable/expirable; many applicants
  may use one link.
- **Document** — uploaded files (pay stubs, ID). Sensitive; subject to retention policy
  (open question).
- **Reference / ReferenceResponse** — references are contacted by dwellow; the response
  (if any) attaches back.
- **Scorecard / Criterion** — the per-unit rules; defaults seeded, customizable.
  **Deferred** in v1 (scoring is holistic) — see
  [ADR 0006](../decisions/0006-score-via-agent-engine.md).
- **Agent** — the polymorphic AI engine (`morphTo analyzable`). `type` selects the
  workflow (`score` in v1); a unique `(analyzable_type, analyzable_id, type)` index
  enforces **one Agent per subject per type**. Holds run state: `status`, `provider`,
  `model`, `raw_response`, `usage`, `error`, `started_at`/`completed_at`. Reusable for
  future workflows (e.g. maintenance-request triage).
- **Score** — the `score` Agent's output and **`belongsTo Agent`**; **1:1 with an
  Application** (re-runs mutate in place). A holistic **`fit_score` (0–100)** plus a
  one-sentence rationale, a neutral summary, **Flags**, and strengths. The rationale
  carries the explainability requirement in v1; per-criterion `CriterionResult` is
  deferred with Scorecard/Criterion.

## Lifecycle / status fields

- **Application.status**: `New → Reviewing → Approved | Rejected`.
- **Reference.status**: `Requested → Reminded → Responded | NoResponse`.
- **Agent.status**: `Pending → Processing → Completed | Failed` (job may retry; a
  Score row exists only once the Agent is `Completed`). Status lives on the **Agent**,
  not the Score.

## Open / to-confirm

- Should `Criterion` defaults be seeded globally and copied per-unit, or referenced?
  (Affects how customization works.) Tracked in
  [open-questions](../open-questions.md).
- One submission per applicant per link assumed (no edit/resubmit).
