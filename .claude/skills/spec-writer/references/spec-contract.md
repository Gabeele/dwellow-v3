# The Spec Contract

The structure every issue must satisfy. Goal: an issue that's unambiguous about the **product** — what to build, for whom, and how you'll know it works — while leaving **how** to build it to the repo.

Two rules sit above the sections:

- **No assumptions.** Anything you'd have to guess is a question for Gavin, not a default. Don't finalize while an assumption is open.
- **Implementation is deferred.** Don't name files, classes, patterns, or libraries — the repo's harness (`CLAUDE.md` + the Laravel Boost guidelines + the domain skills in `.claude/skills/`) and the implementing agent's planning step decide that. The only exception: an implementation detail Gavin explicitly asks for, captured as a hint.

## The sections

1. **Context** — what exists today and what this touches, in product terms.
2. **Problem** — the pain, who has it, why now.
3. **Solution** — the behaviour/outcome from the user's view. WHAT, never HOW.
   > Good: "A landlord can mark a unit as vacant and see vacant units filtered out of the active-tenant list."
   > Bad: "Add a nullable `vacated_at` column and an Inertia filter on the units page." (That's the repo's call.)
4. **Keep in mind** — edge cases, existing data, states, limits, permissions to respect.
5. **Guidelines & understanding** — product rules specific to this feature (not repo coding rules).
6. **Scope (in / out)** — in product terms (flows/areas). A file/area list is optional and only if Gavin volunteered it. **Out** is required.
7. **Acceptance criteria** — binary Given/When/Then checks; each must be verifiable.
8. **Tests** — the behaviours that must be proven (the repo decides the framework — here, Pest). Name the scenarios, including edge/permission paths.
9. **Design** — UI/UX notes, copy, mockups/links — when visual. Otherwise blank.
10. **Ultimate goals** — what success means for the user/business.

Plus a standing **Implementation (deferred)** line: *"Implementer follows the repo harness (`CLAUDE.md` + Laravel Boost guidelines + `.claude/skills/`) and may use the Plan agent and `search-docs` to choose patterns; product-owner hints below, if any."*

## Why two sections are required beyond the original list

- **Scope — out** and **Acceptance criteria** are the biggest failure-preventers. Both required.

## Over- vs under-specifying (product level)

Under-specify and the implementer has to guess the product. Over-specify the *how* and you've done the repo's job for it — usually wrong, and it removes better solutions. Stay on outcomes, scope, and acceptance; pass any explicit implementation wish along as a hint.

## dwellow-v3 specifics

- This is a Laravel 13 + Inertia v3 + Vue 3 app, early stage. When you reference existing behaviour, verify it against the repo (models in `app/Models`, pages in `resources/js/pages`, routes) rather than assuming features exist.
- Use the app's real domain language. If a term is ambiguous or not yet established in the code, ask Gavin rather than inventing one.
- The repo already carries domain skills (`fortify-development`, `inertia-vue-development`, `laravel-best-practices`, `pest-testing`, `tailwindcss-development`, `wayfinder-development`). Trust the implementer to activate these — do not restate their rules in the issue.

## Readiness bar

Ready to hand off only when all hold:

1. **Solution/behaviour** is outcome-focused (no implementation prescribed unless Gavin hinted it).
2. **Acceptance criteria** are binary and verifiable.
3. **Scope — out** is filled.
4. **No assumptions remain open** — every gap was answered by Gavin, not guessed.
