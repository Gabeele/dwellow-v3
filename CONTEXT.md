# CONTEXT.md — dwellow-v3

**Read this first.** This is the single orientation file for anyone (human or agent) working in
this repo: what dwellow is, who it's for, how the domain is shaped, how the code is arranged, and
where it's headed. It points into `.docs/` for depth — it does not duplicate it. When this file and
the code disagree, the code wins; fix this file.

> Sibling context: `CLAUDE.md` (coding guidelines + package versions), `.docs/` (product, domain,
> decisions), `.claude/skills/` (how-to skills), `.claude/agents/` (delegable subagents),
> `ralph.md` + `PROMPT.md` + `ralph.sh` (the autonomous loop).

---

## 1. What dwellow is

dwellow makes **tenant screening** fast, structured, and repeatable for **small DIY landlords
(1–20 units)**. Today screening is slow and manual — collecting documents over email/text, chasing
references by phone, eyeballing pay stubs with no consistent way to compare applicants. Picking the
wrong tenant is one of the most expensive mistakes a landlord can make.

The flow: a landlord builds a **custom application form** for a unit and shares a **link**;
applicants self-serve (fill it out, upload documents, list references); dwellow contacts references
and an **AI scoring job** evaluates each submission; the landlord sees every applicant side-by-side
with an **AI-generated `fit_score`** and decides quickly.

**Deliberate v1 boundary:** dwellow uses **applicant-provided documents**, *not* regulated credit-
bureau reports — this avoids becoming a Consumer Reporting Agency and ships far faster
(`.docs/decisions/0002-no-bureau-integrations.md`). Applicants use **link-only** access, no account
(`.docs/decisions/0003-link-only-applicants.md`). See `.docs/product/overview.md` and `personas.md`.

## 2. Direction (why this matters for what you build)

Screening is the **front door**, not the whole house. North star: the small landlord's all-in-one
operating system — **screening → lease & e-sign → rent collection → maintenance → accounting**
(`.docs/roadmap.md`).

**Guiding rule: don't broaden until screening is genuinely loved.** A great single-purpose tool
beats a shallow all-in-one. So work priority is: make screening best-in-class first; expand the
lifecycle only behind a spec/ADR. When you pick up a task, prefer the one that deepens the screening
wedge over one that broadens surface area — unless a spec says otherwise.

The **`Agent` engine is polymorphic on purpose** (see §4): every future lifecycle step that needs AI
(maintenance triage, lease drafting, reference summarising) is a *new agent type* reusing the same
pipeline, not a new bespoke integration.

## 3. Domain model (conceptual)

Full version: `.docs/domain/data-model.md`; terms: `.docs/domain/glossary.md`.

```
User (Landlord)
 └─1:N─ Property
          └─1:N─ Unit
                   ├─1:1─ ApplicationForm     (current field schema, editable JSON)
                   ├─1:N─ ApplicationLink     (shareable, revocable/expirable)
                   └─1:N─ Application
                            ├─ form_snapshot  (schema as submitted — renders stably)
                            ├─1:N─ Document    (uploads: pay stub, ID, credit report — PRIVATE disk)
                            ├─1:N─ Reference ─1:0..1─ ReferenceResponse
                            ├─ morphOne Agent (type=score; one per subject per type)
                            └─1:1─ Score ─belongsTo─ Agent
Agent (polymorphic AI engine)  └─ morphTo analyzable   (Application now; more later)
Activity  └─ morphTo subject   (timeline: AnalysisStarted/Completed/Failed, …)
```

**Key invariants**
- **Screening happens at the Unit level.** A Property groups Units.
- **`form_snapshot`** freezes the form at submit time so a submission always renders as submitted.
- **Score is holistic in v1**: a 0–100 `fit_score` + rationale + neutral summary + red_flags +
  strengths + an 8-criterion rubric. The per-criterion *Scorecard/Criterion* engine is **deferred**
  (`.docs/decisions/0006-score-via-agent-engine.md`).
- **One `Agent` per subject per type**, mutated in place on re-runs (status lives on the Agent, not
  the Score). A `Score` row exists only once its Agent is `Completed`.
- **Status machines:** `Application: New → Reviewing → Approved|Rejected` ·
  `Reference: Requested → Reminded → Responded|NoResponse` ·
  `Agent: Pending → Processing → Completed|Failed`.

## 4. Architecture

**Stack:** PHP 8.5 · Laravel 13 · Filament (admin) · Inertia v3 + Vue 3 (Composition API, TS) ·
Tailwind 4 · Pest 4 · Larastan 3 · **`laravel/ai` ^0.8.1** · MySQL/MariaDB + Redis, all under
**Laravel Sail** (Docker). Queue is database/redis-backed.

**Screening request flow**
```
Applicant → GET/POST /screening/{link:token}   (PublicScreeningController; Inertia "screening/Apply")
  → ApplicationService::createApplication()      (snapshot form, store answers, move docs to private disk)
  → dispatch ScoreApplication (queued job)
      → ApplicationScoringService::run(Application)   (the "score" AgentHandler)
          1. startAgent()          firstOrNew 1:1 Agent, mark Processing, recordActivity
          2. DocumentTextExtractor  extract text from uploaded PDFs/images
          3. ScorePrompt::forApplication(app, docText)   build system+body prompt
          4. (new ScoreAgent)->prompt($p, provider: config('ai.default'))   structured output, temp 0
          5. ScoreResponseValidator::validate()  → one repair retry on failure
          6. complete (persist Score, usage, raw_response) OR fail (store error, no Score)
Landlord → Inertia dashboard: /applications, /applicants/{application} (score, docs, timeline),
           approve/reject (emails).  Admin extras via Filament.
```

**The Agent engine (product AI layer).** One pipeline, many agent types via `App\Screening\AgentHandler`
(`run(Model): Agent`). Today: `AgentType::Score`. To add an agent type, replicate the Score pieces —
see the **`agent-engine` skill** (authoritative recipe) and the reference files:
`app/Screening/Agents/ScoreAgent.php`, `ScorePrompt.php`, `ScoringFramework.php`,
`ApplicationScoringService.php`, `ScoreResponseValidator.php`, `app/Models/{Agent,Score}.php`,
`app/Jobs/ScoreApplication.php`.

**Provider policy:** always `config('ai.default')` — **Ollama** locally (`llama3.1:8b`, never a paid
endpoint in dev), **Anthropic** in prod (`AI_PROVIDER=anthropic`). Never hardcode a provider/model.
Reproducible work runs at **temperature 0**.

**Fair-housing is a hard constraint, not a feature.** Anything that reasons over people must restrict
to permissible factors (income/rent-to-income, employment *stability*, credit, references, occupancy
fit, application consistency, disclosed issues) and must never score, infer, or mention a protected
class or proxy. The prompt forbids it, the validator rejects leakage, and the `fair-housing-auditor`
subagent + `screening:eval-prompt` guardrail check enforce it. A leak is an automatic fail that
outranks every other consideration. Not legal advice — legal review is still required pre-launch
(`.docs/open-questions.md`).

## 5. Code map

```
app/
  Screening/            the AI engine (agents, prompts, framework, scoring service, validators, eval)
    Agents/             laravel/ai Agent classes (ScoreAgent)
  Models/               Eloquent: Application, Document, Reference*, Agent, Score, Unit, Property, User, …
  Jobs/                 queued triggers (ScoreApplication)
  Http/Controllers/     PublicScreeningController (applicant), ApplicationController (landlord), …
  Observers/            UnitObserver seeds ApplicationForm + ApplicationLink on Unit creation
  Enums/                AgentType, AgentStatus, ApplicationStatus, ActivityType, CriterionAssessment, …
  Console/Commands/     screening:eval-prompt, screening:seed-samples
  Filament/             admin panel resources/pages
resources/js/           Inertia v3 pages (pages/), Vue components (components/), composables/
routes/                 web.php (public + landlord), console, etc.
tests/                  Pest — Feature/, Unit/, Fixtures/screening-samples/ (strong|borderline|redflag)
.docs/                  product/ personas/ scope · domain/ · decisions/ (ADRs) · features/ · roadmap · open-questions
docs/agents/            issue-tracker, triage-labels, domain (how agents consume docs)
```

## 6. Conventions that matter here (see CLAUDE.md for the full list)

- **Sail everything:** prefix PHP/Artisan/Composer/Node with `vendor/bin/sail`.
- **`search-docs` (Boost) before using any framework feature** — versions are specific (Laravel 13,
  Inertia v3, Filament, `laravel/ai`). Don't guess APIs.
- **Do it the Laravel way:** `sail artisan make:*` (`--no-interaction`); `database-schema` before
  migrations; restate all column attributes when altering.
- **PHP:** curly braces always; constructor property promotion; explicit return types + param hints;
  casts in `casts()`; TitleCase enum keys; PHPDoc over inline comments; array-shape PHPDoc.
- **Frontend:** Inertia v3 + Vue 3 single-root components; typed `defineProps<>()`; Wayfinder route
  helpers (`@/actions`, `@/routes`) — never hardcode URLs; Tailwind 4; deferred props get a skeleton.
- **Tests are mandatory** and use **Pest** (`sail artisan test --compact --filter=…`). Fake the agent
  in tests (`ScoreAgent::fake([...])`) — never hit a live model. `screening:eval-prompt` is NOT a
  test and must never run under `artisan test`.
- **Format/lint:** `sail bin pint --dirty --format agent` (PHP); eslint + prettier for `resources/js`.
- **ADRs live in `.docs/decisions/`** (not `docs/adr/`). Record real decisions as ADRs; record
  unresolved ones in `.docs/open-questions.md`.

## 7. The autonomous loop (how work gets done unattended)

`ralph.sh` restarts a fresh Claude Code agent each iteration; the agent reads **`ralph.md`** (the
backlog), does the single most important task, verifies it (tests + pint + review), checks it off,
commits locally (**never pushes**), and stops. It can delegate to the subagents in `.claude/agents/`.
Standing instructions: `PROMPT.md`. Loop mechanics + track discipline: the **`harness-orchestration`
skill**. The screening prompt-tuning track is reward-driven against `screening:eval-prompt` (the
**`screening-eval` skill**).
```

## 8. Pointers

| Need | Go to |
| --- | --- |
| Product, users, why | `.docs/product/`, `.docs/roadmap.md` |
| Decisions (ADRs) | `.docs/decisions/` |
| Domain detail / glossary | `.docs/domain/` |
| Feature specs | `.docs/features/` |
| Open questions (unresolved) | `.docs/open-questions.md` |
| Coding rules + versions | `CLAUDE.md` |
| Build a new AI agent type | `.claude/skills/agent-engine/` |
| Run/read the scoring eval | `.claude/skills/screening-eval/` |
| The loop | `ralph.md`, `PROMPT.md`, `.claude/skills/harness-orchestration/` |
