# Feature: Landlord Dashboard (Compare & Decide)

## Purpose

The payoff screen. Where the landlord sees every applicant for a unit with their
AI-generated score and decides — quickly, fairly, and with the red flags surfaced.

## Views

### 1. Unit applicant list (compare/contrast)

A table/board of all applicants for a unit:

- **Score / bucket** (sortable) — the headline.
- **Key fields** — income, rent-to-income, employment, occupancy.
- **Flags** — anything `needs review` or a hard-fail.
- **Reference status** — responded / pending / no response.
- **Application status** — New, Reviewing, Approved, Rejected.

Sorting by score lets the landlord triage top applicants instantly; flags stop a high
score from hiding a problem.

### 2. Single application detail

Open one applicant to see:

- The full submitted form (rendered from its snapshot),
- Uploaded documents,
- Reference responses,
- **Per-criterion breakdown** with the score's rationale (see
  [scoring engine](./scoring-engine.md) — explainability is required),
- Actions: **approve / reject / mark reviewing**, and notes.

## Design notes

- **Score + rationale always together.** Never show a number without the why.
- **"Unverified" labeling.** The UI consistently signals that data is applicant-provided.
- **Decision is the landlord's.** dwellow recommends; it never auto-rejects an applicant.

## Open questions

- Does approving/rejecting notify the applicant automatically? **Assumed yes** for
  "received/under review"; explicit decision messaging is a UX/legal choice — tracked in
  [open-questions](../open-questions.md).
- A cross-unit "all applicants" overview, or strictly per-unit in v1? *(Per-unit
  assumed; portfolio view is a later nicety.)*
