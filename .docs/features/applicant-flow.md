# Feature: Applicant Flow

## Purpose

Let a prospective tenant complete an application as fast as possible — ideally from a
phone — with **no account required**.
See [ADR 0003](../decisions/0003-link-only-applicants.md).

## Flow

1. Applicant receives the **link** from the landlord (text, email, etc.).
2. Opens it → sees the unit context (address/unit) and the landlord's
   [custom form](./application-form-builder.md).
3. Fills out fields, uploads documents, lists references.
4. **Lightweight identity verification** — confirm email and/or phone via a one-time
   code so submissions are attributable and spammy links are deterred.
5. Submits → an **Application** is created and a confirmation is shown.
6. Receives status updates (received → under review → decision) via email/SMS.

## Constraints & decisions

- **No password / no profile.** The application belongs to the unit's link, not to a
  reusable applicant account. Applicants re-enter info for a different unit. (v1
  tradeoff for lowest friction.)
- **Link integrity.** A link should be:
  - tied to one unit,
  - revocable / expirable by the landlord,
  - able to accept multiple applicants (the landlord shares it widely).
- **Sensitive data.** Uploaded documents (pay stubs, IDs) require careful storage and
  retention handling — see [open-questions](../open-questions.md) (data retention).

## Open questions

- Can an applicant edit/resubmit after submitting? **Assumed no** in v1 (one
  submission per applicant per link); landlord can re-share if needed.
- Verification channel: email only, or email **and** SMS? Tracked in open-questions.
