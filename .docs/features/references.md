# Feature: Automated Reference Outreach

## Purpose

Remove the most-skipped, most-tedious part of screening: calling references. dwellow
**contacts references automatically** and attaches their responses to the application.

## Flow

1. Applicant lists references in the form (name, email, relationship — e.g. prior
   landlord, employer, personal).
2. On submission, a background job **emails each reference** a short, purpose-built
   form (e.g. prior-landlord questions differ from employer questions).
3. The reference responds via a no-login link; the response is attached to the
   application.
4. Reference responses **feed the [scoring engine](./scoring-engine.md)** and appear on
   the landlord [dashboard](./landlord-dashboard.md).

## States per reference

`Requested → Reminded → Responded` — or `No response` after a timeout.

The dashboard must show reference status clearly so a landlord knows whether a low
score is due to a genuine red flag or simply a non-responding reference.

## Design notes

- **Templated by relationship type.** Prior-landlord vs employer vs personal get
  different question sets.
- **Reminders + timeout.** One or two reminders, then mark `No response`; never block
  the landlord's decision waiting on a reference.
- **Anti-gaming awareness.** References are applicant-supplied contacts — the product
  should not over-trust them. The score treats reference input as one signal, not proof.

## Open questions

- How many reminders, and over what window? Tracked in
  [open-questions](../open-questions.md).
- Do we verify a reference is who they claim to be? *(Out of scope v1 — treat as
  self-reported, consistent with the no-bureau stance.)*
