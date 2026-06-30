# Glossary (Ubiquitous Language)

Use these terms consistently in code, UI, and docs. One word per concept.

| Term | Definition |
| --- | --- |
| **Landlord** | The primary user. A DIY owner who registers, manages properties/units, and screens applicants. One account = one landlord (v1). |
| **Property** | A building/address owned by a landlord. Contains one or more units. |
| **Unit** | The specific rentable space being screened for. Owns its form, link, criteria, and applicant pool. (A whole single-family home is a unit too.) |
| **Application Form** | The landlord-customized schema of fields an applicant fills out for a unit. Snapshotted onto each submission. |
| **Application Link** | The shareable URL (per unit) a landlord sends to prospective tenants. Revocable/expirable; accepts multiple applicants. |
| **Applicant** | A prospective tenant who opens a link and submits an application. **No account** — identified by verified email/phone. |
| **Application** | A single applicant's submission for a unit: their form answers, uploaded documents, references, and resulting score. |
| **Reference** | A contact (prior landlord, employer, personal) listed by an applicant. dwellow emails them a form; their **Reference Response** attaches to the application. |
| **Criterion** | A single screening rule (e.g. "income ≥ 3× rent"). Defaults shipped by dwellow, customizable per unit. *(Deferred — v1 scoring is holistic; see [ADR 0006](../decisions/0006-score-via-agent-engine.md).)* |
| **Scorecard** | The set of criteria applied to applications for a unit. *(Deferred alongside Criterion.)* |
| **Agent** | The polymorphic AI engine that runs a workflow over a subject. `morphTo analyzable` so the same machinery powers future workflows (e.g. maintenance-request triage); `type` selects the workflow. **One Agent per subject per type.** Carries run state (status, provider, model, raw response, usage, timings). See [ADR 0006](../decisions/0006-score-via-agent-engine.md). |
| **Score** | The AI-generated result of evaluating an application — **produced by an Agent** of type `score`. **1:1 with an Application.** A holistic **`fit_score` (0–100)** plus a one-sentence rationale, a neutral summary, **Flags**, and strengths. *(v1 is holistic; per-criterion outcomes are deferred — see [ADR 0006](../decisions/0006-score-via-agent-engine.md).)* |
| **Flag** | A surfaced concern on an application (`needs review`, hard-fail, missing reference). On a Score, Flags are *permissible concerns only* (fair-housing safety). |
| **Scoring Job** | The background job (`ScoreApplication`) that runs the Score Agent over a submission and produces its Score. |

## Naming guidance

- Prefer **Applicant** over "tenant" until approved — they aren't a tenant yet.
- Prefer **Unit** as the screening anchor; **Property** only groups units.
- Say **Score** for the AI output; never call it a "report" (avoids implying a
  regulated consumer report).
