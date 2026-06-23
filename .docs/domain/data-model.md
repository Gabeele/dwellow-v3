# Data Model (Conceptual)

This is the **product-level** model — the entities and relationships, not migrations.
Implementation (tables, columns, types) lives in `database/migrations`.

## Entities & relationships

```
User (Landlord)
  └─1:N─ Property
            └─1:N─ Unit
                     ├─1:1─ ApplicationForm (current schema)
                     ├─1:1─ Scorecard ──1:N── Criterion
                     ├─1:N─ ApplicationLink
                     └─1:N─ Application
                               ├─ form_snapshot (the schema as submitted)
                               ├─1:N─ Document (uploads)
                               ├─1:N─ Reference ──1:0..1── ReferenceResponse
                               └─1:1─ Score ──1:N── CriterionResult
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
- **Score / CriterionResult** — the scoring job's output: an overall score plus a
  per-criterion outcome **with rationale** (explainability requirement).

## Lifecycle / status fields

- **Application.status**: `New → Reviewing → Approved | Rejected`.
- **Reference.status**: `Requested → Reminded → Responded | NoResponse`.
- **Score.status**: `Pending → Scored | Failed` (job may retry).

## Open / to-confirm

- Should `Criterion` defaults be seeded globally and copied per-unit, or referenced?
  (Affects how customization works.) Tracked in
  [open-questions](../open-questions.md).
- One submission per applicant per link assumed (no edit/resubmit).
