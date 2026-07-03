---
name: agent-engine
description: "ACTIVATE when building or extending a product AI agent in dwellow-v3 on the laravel/ai engine — a new AgentType (e.g. reference-checker, maintenance-triage, lease-drafter, application-summary), or changing how an agent runs, validates, retries, or persists. This is the authoritative recipe for the polymorphic Agent pipeline. Do NOT activate for prompt tuning of the existing Score (use screening-eval) or for the claude -p build loop (use harness-orchestration)."
license: MIT
metadata:
  author: gavin
---

# Agent Engine (dwellow-v3)

dwellow-v3 runs product AI agents on **`laravel/ai` ^0.8.1** through one polymorphic pipeline. There is exactly **one** agent type today — `Score` — and it is the reference implementation for every future type. This skill is the recipe: copy the Score pattern, don't reinvent it.

## The invariants (never break these)
- **Provider is `config('ai.default')`** — Ollama locally, Anthropic in prod. Never hardcode a provider or model name.
- **Temperature 0** for anything a user must be able to rely on/justify (reproducibility). Use sampling only when the task explicitly wants variety.
- **Structured output only.** The agent implements `HasStructuredOutput`; the schema and instructions live in a dedicated `*Prompt` class, not inline in the agent.
- **One handler per type**, implementing `App\Screening\AgentHandler` (`run(Model): Agent`). No registry/manager until a second *need* appears — that's YAGNI (see the `AgentHandler` docblock).
- **1:1 Agent per subject, mutated in place.** Re-runs/retries reuse the same `Agent` row (`firstOrNew`), so status is idempotent.
- **One repair retry.** On a validation failure, re-ask once with the contract violations appended, then give up (mark failed, persist raw payload, write no result). Never loop.
- **Fair-housing safety** for any agent reasoning over people: the prompt forbids protected-class factors and the validator rejects leakage. Route the finished prompt through the `fair-housing-auditor` subagent.

## Reference implementation — read these before you build
| Piece | File |
| --- | --- |
| Agent (laravel/ai) | `app/Screening/Agents/ScoreAgent.php` |
| Prompt (instructions + schema + `forApplication`) | `app/Screening/ScorePrompt.php` |
| Rubric/guidance | `app/Screening/ScoringFramework.php` |
| Handler (start/complete/fail + repair) | `app/Screening/ApplicationScoringService.php` |
| Validator + result | `app/Screening/ScoreResponseValidator.php`, `ScoreValidationResult.php` |
| Handler contract | `app/Screening/AgentHandler.php` |
| Models | `app/Models/Agent.php`, `app/Models/Score.php` |
| Trigger job | `app/Jobs/ScoreApplication.php` |
| Enums | `app/Enums/AgentType.php`, `AgentStatus.php`, `ActivityType.php` |

## Recipe — add agent type `Foo` over subject `Subject`
Work in this order; each step has a Score analogue to copy:

1. **Enum:** add `AgentType::Foo` + its `label()` arm.
2. **Agent:** `app/<Area>/Agents/FooAgent.php` — `#[Temperature(0)] class FooAgent implements Agent, HasStructuredOutput { use Promptable; instructions() → FooPrompt::instructions(); schema($s) → (FooPrompt::schema())($s); }`.
3. **Prompt:** `FooPrompt` with `instructions()` (role + policy + JSON contract), `schema(JsonSchema)`, and `forSubject(Subject $s, ...$context): string`.
4. **Validator:** `FooResponseValidator::validate($structured): FooValidationResult` (`valid`, `value`, `errors`) — normalise to a canonical shape; independent of the SDK.
5. **Result model + migration:** `app/Models/Foo.php`, `belongsTo(Agent)` + subject relation, casts; migration mirrors `scores`.
6. **Handler:** `FooService implements AgentHandler` — mirror `ApplicationScoringService`: `startAgent` (firstOrNew, Processing, `recordActivity(AnalysisStarted)`) → build prompt → `(new FooAgent)->prompt($prompt, provider: config('ai.default'))` → validate → one repair retry → `completeAgent` (save result, `usage`/`raw_response`/`model`, Completed, `recordActivity(AnalysisCompleted)`) or `failAgent`.
7. **Trigger:** a queued `FooSubject` job (`ShouldQueue`, small `tries`/`backoff`/`timeout`, `failed()` marks the Agent Failed) dispatched from the right controller/observer/action — mirror how `ScoreApplication` is dispatched.
8. **Tests:** fake the agent (`FooAgent::fake([...])`); cover valid, invalid→repair→success, invalid→fail, and the trigger. Add fixtures + expectations if an eval is warranted (see `screening-eval`).

## Verify (definition of done)
- `search-docs` (laravel/ai) consulted for any SDK attribute/response type used.
- `vendor/bin/sail artisan test --compact --filter=Foo` green; `vendor/bin/sail bin pint --dirty --format agent` clean.
- End-to-end run against Ollama via a `--sync` seed or `tinker` proves a real result persists (or `[blocked] — Ollama not reachable`).
- If it reasons over people: `fair-housing-auditor` returns PASS.
