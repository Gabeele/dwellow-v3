# dwellow

**Tenant screening for small landlords.** dwellow turns picking a tenant from a slow,
manual scramble — collecting documents over email and text, eyeballing pay stubs with
no consistent way to compare applicants — into a fast, structured, repeatable process.

A landlord builds a custom application form for a unit, shares a link, and applicants
self-serve: they fill out the form and upload their supporting documents. Every
application lands on one dashboard where the landlord can review, take notes, and move
each applicant through a simple status flow.

### Scope (v1)

dwellow v1 is deliberately narrow — see the [ADRs](.docs/decisions/):

- **Documents-only, Canadian, applicant-provided.** dwellow never pulls a credit or
  background bureau report. A "credit report" is a file the applicant uploads
  ([ADR 0002](.docs/decisions/0002-no-bureau-integrations.md)).
- **Link-only applicants, no accounts.** Applicants are never asked to register; they
  apply through a shareable per-unit link ([ADR 0003](.docs/decisions/0003-link-only-applicants.md)).
- **CRUD only.** No AI scoring and no automated reference outreach — both are deferred
  ([ADR 0001](.docs/decisions/0001-screening-only-v1.md),
  [ADR 0004](.docs/decisions/0004-ai-generated-score.md)). The longer-term vision lives
  in the [roadmap](.docs/roadmap.md).

More context: [product overview](.docs/product/overview.md),
[glossary](.docs/domain/glossary.md), [data model](.docs/domain/data-model.md).

## Stack

- **PHP 8.5** / **Laravel 13**
- **Inertia v3** + **Vue 3** + **TypeScript** (single-page app, server-side routing)
- **Tailwind CSS v4**
- **Laravel Fortify** for landlord authentication
- **Laravel Wayfinder** for typed route helpers in the frontend
- **Pest v4** for tests, **Pint** for PHP formatting, **Larastan** for static analysis,
  **ESLint** / **Prettier** / **vue-tsc** for the frontend
- **Laravel Sail** (Docker) for local development — MariaDB, Redis, and Mailpit

## Local setup

This project runs inside [Laravel Sail](https://laravel.com/docs/sail), so every PHP,
Artisan, Composer, and Node command is prefixed with `vendor/bin/sail`.

```bash
# 1. Install PHP dependencies (uses your local PHP/Composer just once to pull Sail in).
composer install

# 2. Create your env file and app key.
cp .env.example .env

# 3. Start the Docker services (app, MariaDB, Redis, Mailpit).
vendor/bin/sail up -d

# 4. Generate the key, run migrations, install + build the frontend.
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
vendor/bin/sail npm install
vendor/bin/sail npm run build
```

Then either build assets once (`vendor/bin/sail npm run build`) or run the Vite dev
server for hot reload while developing:

```bash
vendor/bin/sail npm run dev
```

Open the app with `vendor/bin/sail open`. Outbound mail (applicant confirmations,
landlord notifications) is captured by **Mailpit** at <http://localhost:8025>.

> The default `.env.example` uses a `sqlite` database, which is the quickest way to get
> running. To use the MariaDB container instead, point `DB_CONNECTION=mysql` (and the
> `DB_HOST=mariadb` / credentials) at the Sail service.

## Running tests

The suite is Pest. Run it through Sail, and prefer a filter or filename to keep it fast:

```bash
vendor/bin/sail artisan test --compact
vendor/bin/sail artisan test --compact --filter=ApplicationSubmissionTest
```

Other quality checks:

```bash
vendor/bin/sail bin pint                 # format PHP (add --dirty to scope to changes)
vendor/bin/sail php vendor/bin/phpstan analyse   # Larastan static analysis
vendor/bin/sail npm run lint             # ESLint (--fix)
vendor/bin/sail npm run types:check      # vue-tsc
```

## The Ralph loop

This repo is built incrementally by an autonomous agent loop ("Ralph"). Three files
drive it:

- **`PROMPT.md`** — the standing instructions. Each iteration is a fresh agent that does
  exactly one task: it reads `ralph.md`, picks the single most important unchecked task,
  implements and verifies it (tests green, Pint clean, no new TS/ESLint errors), checks
  it off, and commits locally.
- **`ralph.md`** — the source of truth for what's left to do: a prioritized checklist of
  tasks, each with the context the agent needs and a note recording what was done.
- **`ralph.sh`** — drives the loop unattended, restarting a fresh agent for each task
  until every item is checked off (the agent prints `RALPH-DONE`) or a safety cap is hit.
  It commits but **never pushes** — review `git log` and push yourself.

```bash
./ralph.sh            # run up to 25 iterations
./ralph.sh 5          # cap at 5 iterations
```

Per-iteration transcripts are written to `storage/logs/ralph/` (git-ignored).
