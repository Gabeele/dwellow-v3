# Feature: Screening — End-to-End

The screening flow is the entire v1 product. This doc is the map; each step links to
its own detailed doc.

## The flow

```
Landlord registers ──► adds Property + Unit ──► builds custom Form ──► shares Link
                                                                          │
                                                       Applicant opens link (no account)
                                                                          │
                                              fills form + uploads docs + lists references
                                                                          │
                                                            submits ──► Application created
                                                                          │
                                   ┌──────────────────────────────────────┤
                                   ▼                                      ▼
                        Reference outreach job                   AI scoring job
                     (emails refs, collects replies)      (parses, scores vs criteria)
                                   └──────────────────────────────────────┤
                                                                          ▼
                                              Landlord dashboard: scores, flags, compare
                                                                          │
                                                            Decision (approve / reject)
```

## Step-by-step → docs

1. [Landlord onboarding](./landlord-onboarding.md) — register, add properties & units.
2. [Application form builder](./application-form-builder.md) — the dynamic custom form.
3. [Applicant flow](./applicant-flow.md) — the link-based, account-free submission.
4. [References](./references.md) — automated reference outreach.
5. [Scoring engine](./scoring-engine.md) — the AI background job and the scorecard.
6. [Landlord dashboard](./landlord-dashboard.md) — compare/contrast and decide.

## Design principles

- **Fast over exhaustive.** Every step optimizes the landlord's time-to-decision.
- **Catch issues anyway.** Speed must not hide red flags — the score surfaces them.
- **Self-reported, clearly labeled.** v1 data is applicant-provided; the UI never
  implies it's verified by a bureau.
