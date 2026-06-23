# Personas

## 1. The Landlord — *primary user*

**Who:** A DIY small landlord with 1–20 units. Self-manages. Not a professional
property manager. Time-poor, price-sensitive, not a software power-user.

**Goals**
- Screen applicants quickly without spending evenings on phone calls and email threads.
- Feel confident they're not missing a red flag.
- Compare multiple applicants fairly and consistently.

**Frustrations today**
- Documents scattered across email/text.
- No consistent yardstick — every applicant evaluated differently.
- Chasing references is tedious and often gets skipped.

**What success looks like:** Opens dwellow, sees three applicants ranked with scores
and flags, and decides in minutes.

## 2. The Applicant (prospective tenant) — *secondary user*

**Who:** Someone the landlord has already met/shown the unit to. Applies via a shared
link. **No account required** (see [ADR 0003](../decisions/0003-link-only-applicants.md)).

**Goals**
- Apply quickly from a phone, with minimal friction.
- Know their application was received and where it stands.

**Frustrations today**
- Filling out paper/PDF forms and emailing sensitive documents.
- No visibility after they hit send.

**Constraint:** Because there's no account, an application is tied to a single
property link — applicants can't reuse one profile across units (v1 tradeoff).

## 3. The Reference — *tertiary, touch-only*

**Who:** A prior landlord, employer, or personal reference listed by the applicant.

**Interaction:** Receives an automated email from dwellow with a short form; their
response is attached to the application. They never log in.
See [references](../features/references.md).
