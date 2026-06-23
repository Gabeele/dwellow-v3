# 0002 — No third-party bureau checks in v1

**Status:** Accepted

## Context

Most tenant-screening products pull regulated reports (credit, criminal, eviction) from
consumer reporting agencies (TransUnion SmartMove, Experian, Checkr, etc.). Doing so
makes the operator a **Consumer Reporting Agency (CRA)** subject to the FCRA, requires
bureau contracts and certification, adverse-action workflows, and significant legal
overhead.

## Decision

v1 does **not** integrate any bureau or run regulated background/credit/eviction
checks. Instead, applicants **self-report and upload supporting documents** (pay stubs,
employment, references, eviction disclosure, credit info they provide). dwellow
organizes and scores this self-reported data; the landlord decides.

## Consequences

- **Pro:** Dramatically faster, cheaper path to launch. Avoids CRA/FCRA burden.
- **Pro:** Sidesteps bureau contracts and certification.
- **Con:** Data is **unverified**. The product must clearly label it as
  applicant-provided and frame the score as a **screening aid, not a verified report**.
- **Con:** Some landlords will still want "real" checks — that's a deliberate later step
  (and a different regulatory posture).
- **Vocabulary:** Never call dwellow's output a "report"; it's a **Score**. (See
  [glossary](../domain/glossary.md).)

## Future

Real bureau checks are a possible later expansion (see
[roadmap](../roadmap.md)) and would require a separate compliance ADR.
