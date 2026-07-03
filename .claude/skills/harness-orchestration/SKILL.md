---
name: harness-orchestration
description: "ACTIVATE when running the dwellow-v3 build harness — the claude -p loop driven by harness/build.sh + harness/BUILD-PROMPT.md over the harness/BUILD.md backlog. Explains how the lead agent picks one task, delegates to the .claude/agents subagents, verifies, and commits. Do NOT activate for the screening prompt-tuning loop (that is ralph.sh / PROMPT.md / ralph.md)."
license: MIT
metadata:
  author: gavin
---

# Harness Orchestration (dwellow-v3)

There are **two** loops in this repo; don't confuse them:
- **Build harness** (this skill): `harness/build.sh` + `harness/BUILD-PROMPT.md` + `harness/BUILD.md` — builds features/agents across the roadmap.
- **Tuning loop**: `ralph.sh` + `PROMPT.md` + `ralph.md` — hill-climbs the screening prompt only.

## The build loop, per iteration
You are a fresh **lead agent**. The filesystem + git are your only memory; `harness/BUILD.md` is the source of truth for what's left.

1. Read `harness/BUILD.md`. Pick the **single most important** unchecked task (top unless something is now more urgent). If all are checked, print `HARNESS-DONE` and stop.
2. **Plan, then delegate.** Do not implement everything yourself — you are an orchestrator. Route the work to the specialist subagent:
   - new product AI agent type → **agent-engine-builder**
   - backend Laravel code → **laravel-implementer**
   - Inertia/Vue frontend → **inertia-vue-implementer**
   - tests / coverage → **test-author**
   - screening prompt gap → **prompt-tuner**
   - docs / ADRs / backlog / delta log → **docs-scribe**
   For a task with backend + frontend + tests, delegate the slices and integrate.
3. **Verify (adversarial).** Run `code-reviewer` on the diff, and — for anything touching screening, prompts, or applicant data — `fair-housing-auditor`. Fix blockers (delegate the fix) before proceeding. A guardrail failure outranks all else.
4. **Definition of done** (all must hold for the code you touched): `vendor/bin/sail artisan test --compact` (relevant filter) green; `vendor/bin/sail bin pint --dirty --format agent` clean; no new TS/ESLint if `resources/js` changed.
5. **Update `harness/BUILD.md`:** check off the task, append a one-line note (what you did + any follow-up as a new unchecked task).
6. **Commit** (`git add -A && git commit`) with a concise message for the one task. **Do not push.**
7. Stop — the loop restarts you for the next task.

## Rules
- **One task per iteration.** Breadth is the loop's job, not yours. No scope-creep.
- Follow `CLAUDE.md` (Sail-prefixed commands, `search-docs` before changes, Laravel conventions).
- If a task is ambiguous or blocked, mark it `[blocked] — <reason>` in `harness/BUILD.md`, pick the next, continue. Don't guess destructively.
- Never delete tests, never force-push, never change dependencies unless the task explicitly says so. Small, reversible commits.
- **Provider safety:** the dev loop runs against Ollama; never wire a paid provider or hardcode a model. If Ollama is unreachable for an agent end-to-end check, mark `[blocked]`.
