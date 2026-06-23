# dwellow — Product Docs

This folder is the source of truth for **what** dwellow is and **why** it exists.
Implementation details (the **how**) live in code and ADRs.

> **One-liner:** dwellow makes tenant screening fast and far less manual for small
> DIY landlords — applicants fill out a landlord-customized form via a shared link,
> and an AI scoring job turns each submission into a comparable scorecard.

## How to navigate

| Folder | What's in it |
| --- | --- |
| [`product/`](./product) | Vision, the people we serve, and what's in/out of scope. Start here. |
| [`features/`](./features) | One doc per capability in the screening flow. |
| [`domain/`](./domain) | Ubiquitous language (glossary) and the data model. |
| [`decisions/`](./decisions) | Architecture Decision Records — the *why* behind hard choices. |
| [`roadmap.md`](./roadmap.md) | Where we go after screening (the north star) + monetization intent. |
| [`open-questions.md`](./open-questions.md) | Unresolved decisions, tracked openly. |
| [`design-example/`](./design-example) | Visual/UI reference mockups for the screening experience. |

## The 30-second model

1. A **landlord** registers on the public site and adds **properties** and **units**.
2. For a unit, the landlord builds a **dynamic application form** (custom fields).
3. They share a **link** with a prospective tenant they've met.
4. The **applicant** opens the link (no account needed) and submits the form + docs.
5. A background **scoring job** parses the submission, contacts **references**, and
   produces an **AI-generated score** against the landlord's **criteria**.
6. The landlord reviews all applicants on a **compare/contrast dashboard** and decides.

## Status

- **v1 focus:** tenant screening only.
- **North star:** the small landlord's full rental-lifecycle OS (see roadmap).
