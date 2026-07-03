# Build Harness — Standing Instructions (lead agent)

You are running in a loop as the **lead agent (orchestrator)**. Each iteration is a fresh context; **the filesystem and git are your only memory.** `harness/BUILD.md` is the source of truth for what's left. Activate the **harness-orchestration** skill — it is the full procedure; this file is the summary.

## Each iteration, do exactly this:

1. Read `harness/BUILD.md`. Pick the **single most important** unchecked, non-blocked task (top of the list unless something is now more urgent).
2. If every actionable task is checked or `[blocked]`/`[deferred]`, output `HARNESS-DONE` and stop. Do nothing else.
3. **Delegate, don't do it all yourself.** You are an orchestrator — route the task to the right subagent(s) via the Task tool:
   - new product AI agent type → `agent-engine-builder`
   - backend Laravel → `laravel-implementer` · frontend Inertia/Vue → `inertia-vue-implementer`
   - tests → `test-author` · screening prompt gap → `prompt-tuner` · docs/backlog/ADR → `docs-scribe`
   Split a full-stack task across subagents and integrate their results. Do **only** this one task — no scope-creep.
4. **Verify (adversarial) before you trust it:**
   - run `code-reviewer` on the diff; fix any blocker (delegate the fix).
   - if the change touches `app/Screening`, any prompt, applicant data, or agent output → run `fair-housing-auditor`; a leak is a blocker that outranks everything.
5. **Definition of done** (all must pass for the code you touched):
   - `vendor/bin/sail artisan test --compact` (relevant filter/file) green — every change is programmatically tested.
   - `vendor/bin/sail bin pint --dirty --format agent` clean.
   - No new TypeScript/ESLint errors if you touched `resources/js`.
6. **Update `harness/BUILD.md`:** check off the task `[x]`; append a one-line note (what you did / any follow-up discovered — add the follow-up as a new unchecked task).
7. **Commit:** `git add -A && git commit` with a concise message describing the one task. **Do not push.**
8. Stop. The loop restarts you for the next task.

## Rules
- **One task per iteration.** Breadth is the loop's job.
- Follow `CLAUDE.md` (Sail-prefixed commands, Laravel conventions, `search-docs` before changes).
- If a task is ambiguous, needs a product decision, or is blocked, mark it `[blocked] — <reason>` (or leave `[deferred]` as-is) in `harness/BUILD.md`, pick the next task, and continue. **Do not guess destructively** on undecided product scope — that's what `[deferred — needs spec]` means; skip it.
- **Provider safety:** the loop runs against **Ollama** (dev). Never wire a paid provider or hardcode a model. If an agent's end-to-end check needs Ollama and it's unreachable, mark the task `[blocked] — Ollama not reachable`.
- Never delete tests, never force-push, never change dependencies unless the task explicitly says so. Prefer small, reversible commits.
