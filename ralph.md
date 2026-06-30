# Application Scoring — Ralph task list

> Format: `- [ ] <task>` for todo, `- [x] <task>` when done, `- [blocked] <task> — reason` if stuck.
> One task = one commit. Most important / most foundational first.
> Context bullets give the agent what it needs; follow `PROMPT.md` for the loop rules and definition of done.

## Where things stand

The full screening **CRUD** flow is built and in git history (per-unit forms, links, the public
applicant flow, the landlord applicants/applications views, document upload/download, the dashboard
signal — see "Done" at the bottom). The previous Ralph plan (email-verification removal + applications
page + polish) is **complete**.

This plan builds the **AI Scoring** feature — colloquially "screening sentiment analysis."
**Canonically it is the `Score`** (the glossary's term for the AI output) and the concerns it surfaces
are **`Flags`**. It realizes the previously-deferred **ADR 0004** via a new, reusable, **polymorphic
`Agent` engine**.

> **This milestone intentionally supersedes the old "CRUD only — no AI scoring" guardrail.** Building the
> Score is the whole point of this plan. It does **not** lift the other deferrals: still no bureau pulls
> (ADR 0002), still link-only/no-account applicants (ADR 0003), still applicant-provided/unverified data.

### What we're building (locked design)

Applicant submits → queued **`ScoreApplication`** job runs the **Agent engine** → engine calls the
**AI SDK** (Ollama locally, Anthropic in prod) over the application's answers + **extracted document
text** → produces a **structured, validated `Score`** (`fit_score`, rationale, neutral summary, **Flags**,
strengths) → written back and surfaced on the **application detail page** + a live **Agents table on the
dashboard**. The **`Agent`** is a *polymorphic engine record* (`morphTo analyzable`) so the same machinery
powers future workflows (e.g. maintenance-request triage). **`Score`** is the result of the `score` agent type.

**Call chain (thin controllers; logic in `App\Screening` services):**

```
PublicScreeningController::store()                 // validate + delegate only
   └─ ApplicationService::createApplication(...)   // move today's inline creation logic here
   └─ ApplicationService::requestScore(app)        // dispatch ScoreApplication ->afterCommit()
            └─ ScoreApplication (queued job)        // first real app/Jobs class
                   └─ ApplicationScoringService::score(app)   // the `score` AgentHandler
                          ├─ ScorePrompt::build(...)          // system prompt + schema + guardrails
                          ├─ DocumentTextExtractor::extract() // PDF -> capped text
                          ├─ AI SDK structured output         // Ollama/Anthropic per config
                          ├─ validate -> 1 repair retry -> fail-clean
                          └─ persist Agent + Score (1:1, updateOrCreate)
```

**Data model:**
- `agents` — polymorphic engine: `morphs('analyzable')`, `type`, `provider?`, `model?`, `status`,
  `raw_response?` (json), `usage?` (json), `error?`, `started_at`, `completed_at`; **unique**
  `(analyzable_type, analyzable_id, type)` (one agent per type per subject).
- `scores` — **1:1 with Application**: `application_id` (unique FK), `agent_id` (FK nullOnDelete),
  `fit_score?` (0–100), `score_rationale?`, `summary?`, `red_flags` (json), `strengths` (json).
- `Application morphMany agents`; `Application::scoreAgent()` (`morphOne where type=score`);
  `Application hasOne Score`; `Score belongsTo Application` + `belongsTo Agent`.
- `AgentType`: `Score`. `AgentStatus`: `Pending/Processing/Completed/Failed`. Re-runs/retries **mutate in
  place** (1:1). UI states: processing = agent pending/processing (no Score yet); failed = agent failed
  (no Score); ready = agent completed (Score present).

**AI response contract** (request via the SDK's structured-output/schema mode, then **validate** —
belt **and** suspenders; on failure → **one repair retry** → else Agent `failed`, raw stored, **no** Score):

```json
{ "fit_score": 0-100, "score_rationale": "one sentence",
  "summary": "2-3 neutral sentences", "red_flags": ["..."], "strengths": ["..."] }
```

**Provider:** config-driven default per type, **no auto-fallback**. Ollama local / Anthropic prod.
**Documents:** extract text (PDF), capped, behind a `DocumentTextExtractor` interface; first impl
`PrinsFrank/pdfparser` (approved dep); image-only docs noted "unreadable" (no OCR v1).

## Guardrails (read before every task)

- **Thin controllers; business logic in `App\Screening` services.** Guiding rule for the whole plan.
- **Hard requirements (ADR 0004 — non-negotiable, in prompt AND UI):**
  - **Fair-housing safety** — prompt considers **only** permissible factors (income, employment,
    rent-to-income, references, occupancy vs unit, completeness/consistency, disclosed evictions) and
    **never** protected classes/proxies (race, color, religion, sex, national origin, familial status,
    disability, age, protected source-of-income). This **tempers** "emphasise red flags" — flags are
    *permissible concerns only*.
  - **Unverified-data framing** — inputs are self-reported/unverified; the Score is a screening **aid**,
    not a decision. Keep the existing UI disclaimer. dwellow never decides for the landlord.
  - **Explainability** — every Score ships a rationale (v1 holistic; per-criterion deferred).
- **Terminology** — say **"Score"** (never "report") and **"Flag"** per `.docs/domain/glossary.md`.
- **Tests** — every change is tested (`Queue::fake()` + the `laravel/ai` fake; **never** hit a real model);
  fixture files for doc extraction. The **existing `PublicScreeningController` feature tests must stay
  green** through the service extraction (refactor safety net).
- **Sail** for all commands; `make:*` generators; Pint `--dirty` before finalizing PHP.
- **Dependencies** — only `PrinsFrank/pdfparser` is pre-approved; nothing else without an explicit task.
- Activate skills as relevant: `inertia-vue-development`, `tailwindcss-development`, `frontend-design`,
  `laravel-best-practices`, `pest-testing`, `fortify-development`.

---

## Milestone 0 — Setup & R&D spikes

- [x] Publish `config/ai.php` and set the default provider per environment
  - context: `vendor/bin/sail artisan vendor:publish` the `laravel/ai` config. Default provider `ollama`
    for local; keep `anthropic` configured for prod (used later). Don't change other providers.
  - done: `config/ai.php` exists; `config('ai.default')` resolves to `ollama` locally; a config test
    or `config:show ai.default` confirms it.
  - note: Published via `--tag=ai-config`. Made `'default' => env('AI_PROVIDER', 'ollama')` so local
    resolves to ollama and prod sets `AI_PROVIDER=anthropic` (no env() branch, stays cacheable). Other
    providers untouched. `tests/Feature/AiConfigTest.php` + `config:show ai.default` confirm ollama.

- [x] Add AI env vars + document local setup
  - context: add to `.env.example` (with comments) `OLLAMA_URL=http://host.docker.internal:11434`
    (the Sail container reaches host Ollama this way — **not** `localhost`), `OLLAMA_MODEL`,
    `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL`. Fill the "Appendix — local setup" at the bottom of this file.
  - done: `.env.example` carries the four keys with comments; appendix updated. (No secrets committed.)
  - note: Added an AI block to `.env.example` (`OLLAMA_URL` via `host.docker.internal`, `OLLAMA_MODEL`,
    blank `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL=claude-sonnet-4-6`) plus a commented `AI_PROVIDER` hint;
    filled the appendix with the full pull→.env→queue:listen steps. No secrets committed. The
    `OLLAMA_MODEL`/`ANTHROPIC_MODEL` defaults are placeholders to be confirmed by the model spike below.

- [x] Spike: confirm the `laravel/ai` v0.8.1 structured-output API
  - context: prove the SDK's schema/structured-output call returns the response contract from both
    `ollama` and `anthropic`. Smallest possible proof (a focused test or throwaway). Record the exact
    API used so `ApplicationScoringService` mirrors it.
  - done: a note in this task (or a committed spike test) showing the structured call + parsed shape.
  - note: Committed `tests/Feature/AiStructuredOutputSpikeTest.php` (2 passing tests, fully faked — no
    real model). **Recorded API:** build a structured agent with the namespaced helper
    `Laravel\Ai\agent(instructions: '...', schema: fn ($schema) => [...])` where the `schema` closure
    receives an `Illuminate\JsonSchema\JsonSchema` factory (`$schema->integer()->min(0)->max(100)`,
    `->string()`, `->array()->items($schema->string())`, all with `->description()`) and returns
    `array<string, Type>` (the object's properties). Invoke with
    `->prompt($text, provider: 'ollama'|'anthropic')` — provider is just an argument, so one code path
    serves Ollama local + Anthropic prod. The result is a `StructuredAgentResponse`: parsed payload on
    `->structured`, also ArrayAccess (`$res['fit_score']`), stringifies to JSON, `->meta->provider`
    carries the provider name. **Faking in tests:**
    `Ai::fakeAgent(StructuredAnonymousAgent::class, [$arrayPayload, ...])` (facade is `Laravel\Ai\Ai`,
    NOT `Laravel\Ai\Facades\Ai`). Follow-up for the scoring service: prefer a dedicated named Agent
    class implementing `HasStructuredOutput` so it fakes by its own class name; the structured call
    shape is identical.

- [x] Spike: PDF text extraction with `PrinsFrank/pdfparser`
  - context: `vendor/bin/sail composer require prinsfrank/pdfparser` (approved). Prove text extraction
    on a committed sample PDF behind a temporary test. Judge quality; if poor, note the fallback plan
    (the `DocumentTextExtractor` interface in Milestone 2 makes swapping cheap).
  - done: extraction returns sensible text from the fixture; dependency added; decision noted.
  - note: Added `prinsfrank/pdfparser ^3.1` (approved). Committed fixture
    `tests/Fixtures/sample-application.pdf` (hand-built minimal valid PDF, correct xref offsets) +
    `tests/Feature/PdfTextExtractionSpikeTest.php` (2 passing tests). **Recorded API:**
    `(new PdfParser)->parseFile($path)->getText()` for a file path, or
    `->parseString($bytes)->getText()` for raw bytes off the storage disk (what the extractor will
    have); `getText(?string $pageSeparator = null)` joins pages. **Quality:** extraction is clean —
    exact text, no garbling, line structure preserved. Good for v1; the `DocumentTextExtractor`
    interface in Milestone 2 keeps a swap cheap if a real-world PDF disappoints. No OCR (image-only
    docs handled by the "unreadable" marker in Milestone 2).

- [blocked] Spike: choose the local Ollama model — needs human/hardware judgment, not sandbox-doable.
  - context: A/B 2–3 candidates (default `qwen2.5:14b-instruct`, e.g. vs `qwen2.5:7b-instruct`,
    `llama3.1:8b`) on 2–3 real applications for the cleanest **validated** JSON. Lock the winner in
    `OLLAMA_MODEL`.
  - done: chosen model recorded in `.env.example` default + appendix; brief rationale noted.
  - note: BLOCKED. Host Ollama is reachable but only has `llama3.2:latest` + a custom community
    `pdurugyan/qwen3.5-9b-deepseek-v4-flash` — none of the named candidates are pulled (would need ~18GB
    of downloads). The judgment also requires the Milestone-2 `ScorePrompt` (doesn't exist yet) and real
    application data, and "locking" a prod default is a subjective quality call that can't be unit-tested
    (loop DoD needs a green test; guardrail forbids hitting a real model in tests). Best done by the user
    once the prompt exists. Revisit after Milestone 2's `ScorePrompt`.

## Milestone 1 — Data model (Agent engine + Score)

- [x] Add `AgentType` and `AgentStatus` enums
  - context: `App\Enums\AgentType` (`Score` → `score`, TitleCase keys, `label()`), `App\Enums\AgentStatus`
    (`Pending/Processing/Completed/Failed`, `label()`). Mirror the existing `ApplicationStatus` enum style.
  - done: a unit test asserting values/labels; enums used by the migration/model below.
  - note: Added `app/Enums/AgentType.php` (`Score => 'score'`) and `app/Enums/AgentStatus.php`
    (`Pending/Processing/Completed/Failed`), both backed string enums with exhaustive `match`-based
    `label()`, mirroring `ApplicationStatus`. Covered by `tests/Unit/AgentTypeTest.php` +
    `tests/Unit/AgentStatusTest.php` (6 tests, asserting labels + string values + case counts). Pint clean.

- [x] Create the `agents` table + `Agent` model
  - context: migration `morphs('analyzable')`, `type`, `provider?`, `model?`, `status`, `raw_response?`
    (json), `usage?` (json), `error?` (text), `started_at?`, `completed_at?`, timestamps; **unique index**
    `(analyzable_type, analyzable_id, type)`. `App\Models\Agent`: `morphTo analyzable`, enum + json casts.
    Add a polymorphic **subject label** + **result URL** accessor that delegates to the analyzable
    (e.g. `$agent->analyzable->agentLabel()/agentUrl()`).
  - done: migration runs; a model test covers the morph relation, casts, and the unique constraint.
  - note: Migration `2026_06_30_021117_create_agents_table` (morphs + unique
    `(analyzable_type, analyzable_id, type)`). `App\Models\Agent`: `#[Fillable]` (mirrors Document/
    Application style), `morphTo analyzable`, casts `type`→AgentType, `status`→AgentStatus, `raw_response`/
    `usage`→array, `started_at`/`completed_at`→datetime. `subject_label`/`result_url` Attribute accessors
    delegate to `analyzable?->agentLabel()/agentUrl()` (Application implements those in a later task; no
    factory yet — that's its own task). `tests/Feature/AgentTest.php` (4 tests): morph relation, casts,
    unique-per-type enforcement, and a different subject getting its own score agent. Pint clean.

- [x] Create the `scores` table + `Score` model
  - context: migration `application_id` (unique FK, cascade), `agent_id` (FK, nullOnDelete), `fit_score?`
    (unsignedTinyInteger), `score_rationale?`, `summary?` (text), `red_flags` (json), `strengths` (json),
    timestamps. `App\Models\Score`: `belongsTo Application`, `belongsTo Agent`, casts (`red_flags`/
    `strengths` → array). Use the glossary term — model is `Score`, not "Summary".
  - done: migration runs; model test covers relations + casts + the 1:1 (unique application_id) invariant.
  - note: Migration `2026_06_30_021335_create_scores_table` — `application_id` unique FK cascadeOnDelete,
    nullable `agent_id` FK nullOnDelete, nullable `fit_score` (unsignedTinyInteger), `score_rationale`
    (string), `summary` (text), nullable json `red_flags`/`strengths`. `App\Models\Score`: `#[Fillable]`
    (Document/Agent style), `belongsTo application`/`agent`, casts `red_flags`/`strengths` → array. No
    factory yet (its own task) — `tests/Feature/ScoreTest.php` builds records manually like AgentTest
    (4 tests): relations, array casts, unique-per-application invariant, agent nullOnDelete. Pint clean.

- [x] Wire `Application` relationships + polymorphic label/url
  - context: on `App\Models\Application`: `morphMany agents`, `scoreAgent()` (`morphOne` constrained to
    `type=score`), `hasOne score`. Implement `agentLabel()` (e.g. "Score — Application: {name}") and
    `agentUrl()` (the applicants.show route) for the dashboard table.
  - done: a test asserts `application->score`, `->scoreAgent`, and the label/url resolve correctly.
  - note: Added `agents()` (`morphMany`), `scoreAgent()` (`morphOne` + `->where('type', AgentType::Score)`),
    `score()` (`hasOne`), plus `agentLabel()` → "Score — Application: {first} {last}" and `agentUrl()` →
    `route('applicants.show', $this)` (default `id` binding). Three new tests in `ApplicationTest.php`
    (relationship resolution, score-type-only filtering / null when none, label+url) — 6 pass. AgentTest's
    `subject_label`/`result_url` delegation now resolves through these. Pint clean.

- [x] Factories for `Agent` and `Score`
  - context: `AgentFactory` (states: `pending/processing/completed/failed`, `forApplication()`),
    `ScoreFactory` (sane `fit_score`, flags, strengths; `for` an Application + Agent). Follow existing
    factory conventions.
  - done: factories instantiate valid records; used by later tests.
  - note: Added `AgentFactory` (default `Score`/`pending`/ollama, morph defaults via
    `(new Application)->getMorphClass()` + `Application::factory()`; states `pending/processing/completed/
    failed` set the matching `started_at`/`completed_at`/`usage`/`error`; `forApplication()` retargets the
    morph) and `ScoreFactory` (sane `fit_score` 40–95, faker rationale/summary, array flags/strengths,
    nullable `agent_id`; `forAgent()` helper — pair with `->for($application)`). Factories run inside
    `Model::unguarded`, so non-fillable morph/FK columns set directly in the definition. Covered by
    `tests/Feature/AgentScoreFactoryTest.php` (6 tests); existing Agent/ScoreTest still green (13 total).
    Pint clean.

## Milestone 2 — Engine, services & job

- [x] Define the `AgentHandler` contract
  - context: `App\Screening\AgentHandler` interface — one method (e.g. `score(Model $analyzable): Agent`,
    or a neutral `run(...)`). Documents the per-type contract. **No registry/manager** (YAGNI until a
    second agent type exists).
  - done: interface committed; `ApplicationScoringService` implements it in a later task.
  - note: Chose the **neutral `run(Model $analyzable): Agent`** over `score()` — the engine's whole point
    is reuse across future agent types (maintenance triage), so a score-specific method name would make
    every future handler misname its method. `app/Screening/AgentHandler.php` documents the per-type
    contract (1:1 mutate-in-place, returns the Agent record); no registry. Covered by
    `tests/Unit/AgentHandlerContractTest.php` (2 tests, reflection-asserts the single `run` method +
    param/return types). Pint clean. NB: later "ApplicationScoringService::score(app)" in the call chain
    should implement `run()` (it may keep a `score()` alias if convenient).

- [x] `DocumentTextExtractor` interface + implementation
  - context: `App\Screening\DocumentTextExtractor` interface; `PrinsFrank`-backed implementation that
    extracts text from a `Document`, **caps** per-doc and total length, and returns an "unreadable" marker
    for image-only/no-text files. Bind the interface in a service provider.
  - done: tests with a committed fixture PDF (returns text) and an image/no-text file (returns the
    unreadable marker); length caps asserted.
  - note: `App\Screening\DocumentTextExtractor` (interface, `UNREADABLE_MARKER` const, `extract(Document)`
    + `extractFromMany(iterable)`) and `PdfDocumentTextExtractor` (PrinsFrank-backed via
    `parseString($bytes)->getText()` off `Storage::disk($doc->disk)`). Only `application/pdf` is parsed;
    images / Word docs / missing-empty-corrupt files short-circuit to the marker (no OCR v1, never throws).
    Caps are constructor params (defaults 10k per-doc / 25k total) so tests drive truncation with a tiny
    limit; `extractFromMany` labels each section `=== {original_name} ===` then caps the join. Bound in
    `AppServiceProvider::register()`. `tests/Feature/DocumentTextExtractorTest.php` (6 tests): PDF text,
    image marker, missing-file marker, per-doc cap, multi-doc concat+labels+marker, total cap. Pint clean.

- [x] `ScorePrompt` builder
  - context: `App\Screening\ScorePrompt` builds the system prompt: the response **schema**, the
    **fair-housing** rule (permissible factors only; no protected-class proxies), the **unverified-data**
    framing, and assembles the applicant's `answers` + `form_snapshot` + extracted document text. Template
    lives here (tunable), not inline in the service.
  - done: a unit test asserts the prompt contains the schema + the fair-housing/unverified guardrail text
    and includes the supplied answers/doc text.
  - note: `app/Screening/ScorePrompt.php` mirrors the SDK's instructions/body split:
    `instructions()` (system prompt — role, UNVERIFIED-DATA framing, FAIR-HOUSING permissible-only factors
    + named protected classes/proxies the model must NEVER weigh, "flags are permissible concerns only",
    and the prose JSON contract) and `forApplication(Application, $docText='')` (renders labelled answers
    via `form_snapshot` like the Filament infolist, then the capped doc text). Also exposes `schema()` —
    the same structured-output closure for the SDK (belt-and-suspenders alongside the prose contract); the
    closure stays untyped (mirrors the spike) and receives the `JsonSchemaTypeFactory` at runtime. Unit
    tests `tests/Unit/ScorePromptTest.php` (6, no DB — in-memory Application) cover schema text, both
    guardrails, unverified framing, body rendering (incl. structured reference + bool + doc text), the
    no-doc fallback, and the schema closure's keys. Pint clean.

- [x] Response validator
  - context: a validator for the contract — `fit_score` int 0–100, required keys present, `red_flags`/
    `strengths` arrays of strings, `summary`/`score_rationale` strings. Returns parsed value or a typed
    failure. Independent of the SDK's structured mode.
  - done: unit tests for valid payload, out-of-range score, missing key, wrong types.
  - note: `App\Screening\ScoreResponseValidator::validate(mixed): ScoreValidationResult`. Chose a **result
    object** over an exception — an invalid payload is the *expected* branch that triggers the service's
    one repair retry, so non-exceptional control flow fits. Hand-rolled the checks (pure PHP, no container/
    SDK) so it stays a true Unit test and matches "independent of structured mode"; needed explicit
    present-but-allow-`[]` logic because Laravel's `required` rejects empty arrays (a "no flags" Score is
    valid). On success it returns exactly the 5 contract keys with normalised types, stripping any extra
    keys the model emitted. `tests/Unit/ScoreResponseValidatorTest.php` (7 tests): valid+normalised value,
    empty flag/strength arrays, extra-key stripping, out-of-range score, missing key, wrong types (string
    fit_score / non-array flags / non-string items), non-array payload. Pint clean.

- [x] `ApplicationScoringService` (the `score` handler)
  - context: `App\Screening\ApplicationScoringService implements AgentHandler`. Flow: create/locate the
    Agent (`processing`, set provider/model/`started_at`) → build prompt (answers + doc text) → AI SDK
    **structured output** (provider from config) → **validate** → on failure **one repair retry** → on
    success persist `Score` + Agent `completed` (`usage`, `completed_at`) via **`updateOrCreate`** (1:1);
    on hard failure mark Agent `failed`, store `raw_response`, write **no** Score.
  - done: feature tests with the `laravel/ai` fake — happy path (Agent completed + correct Score columns);
    malformed payload triggers the repair retry; still-bad → Agent `failed` + no Score row. Pint clean.
  - note: Added a dedicated named agent `App\Screening\Agents\ScoreAgent` (implements `Agent` +
    `HasStructuredOutput`, uses `Promptable`) delegating `instructions()`/`schema()` to `ScorePrompt` —
    chosen over the spike's anonymous `agent()` helper so tests fake it by class
    (`ScoreAgent::fake([...])`, `ScoreAgent::assertPrompted(...)`). `ApplicationScoringService::run()`
    type-guards to `Application` then calls `score()`: `startAgent` does `scoreAgent()->firstOrNew()`
    (1:1, mutate-in-place) → Processing + provider (`config('ai.default')`) + `started_at`; builds the
    prompt from `extractFromMany($application->documents)` + `ScorePrompt`;
    `(new ScoreAgent)->prompt($prompt, provider:)` → validate → on fail one repair retry with the contract
    errors appended → `completeAgent` persists the Score via `score()->firstOrNew()` + `associate($agent)`
    + `fill($value)` (FKs aren't fillable, so the relation/associate set them) and marks the Agent
    Completed (`model` from `meta`, `usage`→array, `raw_response`); `failAgent` (also the catch-all) marks
    Failed, stores the last `raw_response`, writes no Score. `tests/Feature/ApplicationScoringServiceTest.php`
    (5) covers happy-path columns, repair retry, still-bad → failed + no Score, re-run mutates the same
    Agent, non-Application rejected; the `laravel/ai` fake returns array responses verbatim by index, so
    `[bad, good]` drives the retry. Pint clean.

- [x] Fix pre-existing full-suite test isolation leak (state bleeds across files)
  - context: NOT caused by the scoring work — present before this task (confirmed by re-running the full
    suite with the new test file removed). In `vendor/bin/sail artisan test` (whole suite),
    `PublicScreeningControllerTest` "no-form-row provisions default" plus two `ScreeningDraftTest` cases
    fail on accumulated rows ("9 is identical to 1", "8 is identical to 0", `sole()` "2 records were
    found"). All pass in isolation, so an earlier test commits outside the RefreshDatabase transaction
    (likely a real queue/file/separate-connection write). Find the offender and stop it leaking.
  - done: full `vendor/bin/sail artisan test` is green with no per-file ordering dependence.
  - note: Offender was `tests/Feature/DocumentTextExtractorTest.php` — it had **no** `RefreshDatabase`
    (the trait is opt-in per-file here; `tests/Pest.php` has the global `->use()` commented out). Its
    `Document::factory()->make([...])` calls never override `application_id`, so the factory's
    `'application_id' => Application::factory()` BelongsTo resolves to a **real** id by `create()`-ing the
    full parent chain (Application → Form → Unit → Property → User) — committed permanently because nothing
    rolled it back. 7 such `make()` calls leaked ~8–9 Applications/Forms, matching the failing counts. Fix:
    added `uses(RefreshDatabase::class)` to that file (one line). Full `vendor/bin/sail artisan test` now
    green (338 passed) with no ordering dependence. Pint clean.

- [x] `ScoreApplication` queued job
  - context: `app/Jobs/ScoreApplication` (`ShouldQueue`) — first real Job. `$tries=2`, `$backoff=[10,30]`,
    `$timeout=120` (local Ollama is slow). `handle()` resolves `ApplicationScoringService` and calls
    `score($application)`. `failed()` marks the run's Agent `failed` so a dead job never strands a row in
    `processing`. A retry **mutates the same** Agent (1:1).
  - done: a test asserts `handle()` invokes the service; a `failed()` test marks the Agent failed.
  - note: `app/Jobs/ScoreApplication.php` (first real Job) — public `$tries=2`/`$backoff=[10,30]`/`$timeout=120`,
    constructor-promoted `readonly Application $application`. `handle()` type-hints `ApplicationScoringService`
    (container resolves it) and calls `score()`. `failed(?Throwable)` re-reads `scoreAgent()->first()` and marks
    it Failed (error + completed_at) **only** if it exists and isn't already Completed — so a late failure on an
    already-finished run is a no-op, and the 1:1 Agent is never stranded in `processing`. `tests/Feature/
    ScoreApplicationTest.php` (3): handle() invokes service with the right app (mocked), failed() flips a
    processing Agent → Failed with the exception message, failed() leaves a Completed Agent untouched. Pint
    clean (it re-imported the `@see AgentHandler` reference).

- [x] `ApplicationService` — create + dispatch
  - context: `App\Screening\ApplicationService`. `createApplication(...)` moves the **inline creation
    logic** out of `PublicScreeningController::store` (the `DB::transaction`, file storage to
    `applications/{id}`, draft-file migration, applicant confirmation + landlord notification) —
    **behaviour-preserving**. `requestScore(Application $application)` dispatches `ScoreApplication`
    **`->afterCommit()`** (the DB queue shares the DB — never dispatch inside the transaction).
  - done: unit/feature tests for the service; existing submission tests still pass against it.
  - note: `createApplication(ApplicationLink $link, array $answers, ?string $draftCookie): Application` —
    chose to pass the validated `answers` array (not the `Request`) so the service is HTTP-decoupled and
    unit-testable; `validated()` keeps `UploadedFile`s in the array, so files ride along. Lifted the
    field-mapping → `DB::transaction` create+documents → draft-file migration → draft delete/cookie-forget
    → applicant mail + landlord notify verbatim (behaviour-preserving). `requestScore()` does
    `ScoreApplication::dispatch($application)->afterCommit()`. Controller is NOT yet thinned — that's the
    next task. `tests/Feature/ApplicationServiceTest.php` (4): create columns/snapshot/docs, draft-file
    migration + draft cleared, applicant mail + landlord-notified-once, requestScore dispatches the job for
    the right application. Existing `ApplicationSubmissionTest`/`PublicScreeningControllerTest`/
    `ScreeningDraftTest` (40) stay green. Pint clean.

- [x] Make `PublicScreeningController::store()` thin
  - context: reduce `store()` to: validate (`StoreApplicationRequest`) + spam check + `ApplicationService::
    createApplication(...)` + `ApplicationService::requestScore(...)`. No business logic left in the
    controller.
  - done: **all existing `PublicScreeningController`/`ApplicationSubmission` feature tests stay green**;
    a `Queue::fake()` test asserts `ScoreApplication` is dispatched after commit on a valid submission and
    **not** dispatched on validation failure / spam. Pint clean.
  - note: `store()` is now open-gate + spam-gate + `createApplication(...)` + `requestScore(...)` + redirect
    (the `ApplicationService` is method-injected). Deleted the lifted inline logic (field mapping, the
    `DB::transaction`, draft migration/cleanup, mail/notify) and its now-unused imports (ApplicationStatus,
    FieldType, Mail/Notification/Storage/DB/Cookie/Carbon/UploadedFile, etc.). Added 3 `Queue::fake()` tests
    to `ApplicationSubmissionTest`: valid submission pushes `ScoreApplication` for the new app; validation
    failure and spam push nothing. Full submission/draft/controller/service suites stay green (47 passed).
    Pint clean.

## Milestone 3 — Read endpoints for the UI

- [x] Expose the Score on the application detail page
  - context: `ApplicationController@show` passes a `score` payload (fit_score, score_rationale, summary,
    red_flags, strengths) **plus** the score agent **status** (for the processing/failed/ready states) to
    `screening/applicants/Show.vue`. Eager-load to avoid N+1.
  - done: an Inertia assertion test for the three states (no agent / processing / completed).
  - note: `show()` now eager-loads `score` + `scoreAgent` (alongside documents/unit.property) and passes
    two new props: `scoreStatus` (= `scoreAgent?->status->value`, null when no agent) and `score` (a shaped
    payload via a private `scorePayload()` helper, null until a Score exists). Kept them as two independent
    props so the Vue layer owns the processing/failed/ready state machine (mirrors the Agent/Score record
    split). `red_flags`/`strengths` coalesce to `[]` so the frontend always gets arrays. 3 new Inertia
    tests in `ApplicationControllerTest.php` (no agent → both null; processing agent → status processing +
    score null; completed agent + Score → status completed + payload columns). 40 pass. Pint clean.

- [ ] Provide the dashboard "Agents" activity dataset
  - context: `DashboardController` passes a list of **recent + active** agents (newest first) — each with
    the polymorphic subject label, agent type, status, `started_at`/`completed_at` (for elapsed), and the
    result URL. Scope to the current landlord's subjects.
  - done: an Inertia assertion test that the dashboard receives the agents collection with the right shape,
    scoped to the landlord.

- [ ] Wayfinder route(s) for polling
  - context: add/confirm the route(s) the frontend partial-reloads against (likely just `dashboard` +
    `applicants.show` with `only:[...]` props — no new endpoint if partial reload suffices). Regenerate
    typed routes (`npm run` / wayfinder). Import via `@/routes/*` / `@/actions/*` — never hardcode URLs.
  - done: typed routes generated; a smoke assertion that the props reload in isolation.

## Milestone 4 — Frontend

- [ ] Application detail "Score" panel (fill existing placeholders)
  - context: in `resources/js/pages/screening/applicants/Show.vue`, replace the placeholder AI cards (the
    dashed "Dwellow AI summary" card + the `ScoreGauge` placeholder + "Document consistency checks") with
    the real Score: `ScoreGauge` for `fit_score`, the rationale, the summary, **Flags** (emphasised), and
    strengths. Three states — **processing** (`Skeleton`/"Scoring…"), **scored** (result), **failed**
    ("Score unavailable, will retry"). Keep the existing unverified-data disclaimer. Use **"Score"**, never
    "report". Reuse `Card`, `Badge` (`ai`/`warning` tints), `ScoreGauge`.
  - done: vue-tsc + `npm run build` clean; an Inertia/feature test renders each state. No new deps.

- [ ] Dashboard "Agents" table
  - context: add an "Agents" section to `resources/js/pages/Dashboard.vue` using `DataTable` + `TableRow`
    (mirror `screening/applicants/All.vue`). Minimal columns that still identify the agent: type + subject
    label, status (`Badge`), and **elapsed time**. `TableRow clickable` → `router.visit(agent.url)`. Show
    recent + active agents, newest first. Empty state via `EmptyState`.
  - done: vue-tsc + build clean; an Inertia assertion the section renders rows; clicking navigates.

- [ ] Live updates (Inertia polling)
  - context: introduce a partial-reload poll (`router.reload({ only: [...] })` on an interval) for the
    dashboard agents table and the detail panel's processing state, plus a **live-ticking elapsed timer**
    while an agent is `processing`. Poll only while something is `processing`; stop when idle. This pattern
    is new to the app — use the `inertia-vue-development` skill (deferred props / polling).
  - done: manual verify "Processing…" flips to done without refresh; vue-tsc + build clean; a light test
    of the polling-prop wiring where feasible.

## Milestone 5 — Keep `.docs/` honest

- [ ] ADR 0006 — Score via the polymorphic Agent engine
  - context: `.docs/decisions/0006-score-via-agent-engine.md`. Record: Score is realized via a polymorphic
    `Agent` engine; **v1 is holistic** (one `fit_score` + rationale + flags); per-unit **Scorecard/Criterion**
    engine **deferred**; provider config (Ollama local / Anthropic prod, no auto-fallback); document **text
    extraction**; **1:1** Score per application; relationship to ADR 0004.
  - done: ADR committed; referenced from `scoring-engine.md`.

- [ ] Update glossary + data-model
  - context: add **Agent** to `.docs/domain/glossary.md` (the polymorphic AI engine; one per subject per
    type) and note **Score is produced by an Agent**; resolve the score-shape open question to **0–100**
    (`fit_score`). In `.docs/domain/data-model.md` add the `Agent` entity and `Score → belongsTo Agent`.
  - done: docs updated; terms used consistently with the code.

- [ ] Reconcile open questions
  - context: in `.docs/open-questions.md`, mark resolved (provider, score shape, holistic v1) vs explicitly
    deferred (Scorecard/Criterion engine + per-criterion rationale, deterministic criteria, re-score button,
    list sort/filter by score, OCR, provider fallback, normalized flags table, document retention).
  - done: open-questions reflects the post-decision state.

---

## Deferred (out of scope for this plan)

Per-unit **Scorecard/Criterion/CriterionResult** engine and **per-criterion** rationale (blocked on the
landlord default-criteria table in `.docs/features/scoring-engine.md`); **deterministic** criteria
computation (v1 is fully LLM-holistic); manual **re-score** button; application-list **sort/filter by
score**; **OCR** for image-only docs; **automatic provider fallback**; **normalized flags table**;
**document retention** policy.

## Design & domain references

Read the relevant doc before a task: `.docs/decisions/0004-ai-generated-score.md`,
`.docs/features/scoring-engine.md`, `.docs/domain/data-model.md`, `.docs/domain/glossary.md`, and the other
ADRs in `.docs/decisions/`. Frontend: mirror `screening/applicants/All.vue` (tables),
`screening/applicants/Show.vue` (detail cards + `ScoreGauge`), and `resources/js/components/ui/*`.

## Done — previous milestones (in git history)

Screening CRUD (enums, default form, models/migrations/factories/policies, auto-provisioned per-unit form,
form-builder + section toggles, links create/toggle/revoke, public apply + submission + snapshot +
documents, applicants list/detail/status/notes/delete, secure document download, dashboard signal),
whole-rental parity (backing unit), and the email-verification-removal + unified Applications page + polish
milestone (35 tasks). See `git log`.

## Appendix — local setup (fill in during Milestone 0)

```
# 1. Ollama (host machine) — pull the model the container will call.
ollama pull qwen2.5:14b-instruct          # or the model chosen in Milestone 0 spike

# 2. .env  (copy from .env.example). The Sail container reaches host Ollama via
#    host.docker.internal — NOT localhost.
AI_PROVIDER=ollama                        # prod overrides this to `anthropic`
OLLAMA_URL=http://host.docker.internal:11434
OLLAMA_MODEL=qwen2.5:14b-instruct
ANTHROPIC_API_KEY=                        # leave blank locally; set in prod env
ANTHROPIC_MODEL=claude-sonnet-4-6         # used when AI_PROVIDER=anthropic

# 3. Process queued ScoreApplication jobs (composer dev already runs a worker).
vendor/bin/sail artisan queue:listen
```
