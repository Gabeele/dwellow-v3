# 0006 — Score via a polymorphic Agent engine

**Status:** Accepted

## Context

[ADR 0004](./0004-ai-generated-score.md) established *that* scoring is an AI
background job. This ADR records *how* it was built, and resolves the choices 0004
left open (model/provider, score shape, per-criterion vs holistic).

Two forces shaped the design:

- The **Score** (the [glossary](../domain/glossary.md) term for the AI output) is the
  first AI workflow, but not the last — maintenance-request triage and similar
  workflows are foreseeable. We didn't want a one-off scoring pipeline we'd have to
  rebuild for the second use case.
- 0004's "**per-criterion** outcomes with rationale" presumes a landlord
  **default-criteria table** that doesn't exist yet (it's the open ⚠️ input in
  [scoring-engine](../features/scoring-engine.md)). Blocking the whole feature on that
  product decision was the wrong trade.

## Decision

Realize the Score through a **reusable, polymorphic `Agent` engine**.

- **`Agent`** is an engine record with `morphTo analyzable` — the same machinery can
  analyze any subject. `type` (`AgentType::Score`) selects the workflow; a **unique
  `(analyzable_type, analyzable_id, type)`** index enforces **one agent per type per
  subject**. It carries run state (`status`, `provider`, `model`, `raw_response`,
  `usage`, `error`, `started_at`/`completed_at`).
- **`Score`** is the `score` agent's result — **1:1 with `Application`** (unique FK),
  `belongsTo Agent`. Re-runs/retries **mutate in place** (`updateOrCreate`), never
  accumulate rows.
- A thin per-type **`AgentHandler`** contract (`run(Model $analyzable): Agent`) is
  implemented by `ApplicationScoringService`. **No registry/manager** until a second
  agent type exists (YAGNI).
- **Controllers stay thin**; the create/dispatch/scoring logic lives in
  `App\Screening` services. `PublicScreeningController::store()` delegates to
  `ApplicationService`, which dispatches the queued `ScoreApplication` job
  `->afterCommit()`.

### Resolved open questions from 0004

- **Score shape → holistic `fit_score` 0–100** (plus a one-sentence rationale, a neutral
  summary, **Flags**, and strengths). **v1 is holistic, not per-criterion**: one
  LLM-produced judgment over permissible factors. The per-unit
  **Scorecard/Criterion/CriterionResult** engine and per-criterion rationale are
  **deferred** (they're blocked on the landlord default-criteria table). Explainability
  is satisfied holistically — every Score ships a rationale.
- **Provider → config-driven default per type, no auto-fallback.** **Ollama** locally,
  **Anthropic** in prod, selected by `config('ai.default')` (one code path; provider is
  just an argument to the SDK call).
- **Documents → text extraction**, capped, behind a `DocumentTextExtractor` interface
  (first impl `PrinsFrank/pdfparser`); image-only docs are marked "unreadable" (no OCR
  in v1).
- **Validation → belt-and-suspenders.** The SDK structured-output call is paired with an
  independent response validator; on failure → **one repair retry** → else the Agent is
  marked `failed` (raw response stored) and **no Score** is written.

## Consequences

- **Pro:** The second AI workflow reuses the engine, dashboard table, and job pattern —
  only a new `AgentType` + `AgentHandler` is needed.
- **Pro:** Shipping holistic-v1 unblocks the feature without waiting on the
  default-criteria product decision.
- **Pro:** 1:1 mutate-in-place keeps re-scores idempotent and the data model simple.
- **Con:** A holistic single number is less granular than per-criterion outcomes; the
  rationale carries the explainability burden alone.
- **Con:** A polymorphic engine is slightly more indirection than a bespoke scoring
  table for exactly one current consumer.
- **Hard requirements (carried from [ADR 0004](./0004-ai-generated-score.md), enforced
  in the prompt *and* the UI):** fair-housing safety (permissible factors only, no
  protected-class proxies — this tempers "emphasise flags": Flags are *permissible
  concerns only*), unverified-data framing (a screening aid, not a decision), and
  explainability (holistic rationale in v1).

## Open / deferred

- Per-unit **Scorecard/Criterion** engine + per-criterion rationale; **deterministic**
  criteria computation; manual **re-score** button; list **sort/filter by score**;
  **OCR**; **automatic provider fallback**; **normalized flags table**; **document
  retention**. See [open-questions](../open-questions.md).
