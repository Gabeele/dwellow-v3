# Ralph Loop — Standing Instructions

You are running in a loop. Each iteration is a fresh context; **the filesystem and git are your only memory.** `ralph.md` is the source of truth for what's left to do.

**Ground yourself first (every iteration):** read `CONTEXT.md` (what dwellow is + architecture + direction) and `CLAUDE.md` (coding rules), then `ralph.md`. Activate the skill for the domain you're about to touch — don't wait until you're stuck. The loop discipline and the track table live in the `harness-orchestration` skill.

`ralph.md` is organised into **tracks** — **C** (coding/feature), **X** (context/harness upkeep), and **S** (screening prompt-tuning, reward-driven). Each task names its track; follow that track's definition of done.

## Each iteration, do exactly this:

1. Read `ralph.md`. Pick the **single most important** unchecked, non-`[blocked]`/`[deferred]` task (top of its section unless something is now more urgent).
2. If every actionable task is checked, output `RALPH-DONE` and stop. Do nothing else.
3. Do **only that one task** — no scope-creep, no "while I'm here." You are encouraged to **delegate** via the Task tool to the fitting subagent in `.claude/agents/` (`laravel-implementer`, `inertia-vue-implementer`, `test-author`, `agent-engine-builder`, `prompt-tuner`, `docs-scribe`; verify with `code-reviewer` / `fair-housing-auditor`). For a full-stack task, split the slices and integrate.
4. Verify it (see Definition of done). If verification fails, fix it within this same iteration before moving on.
5. Update `ralph.md`: check off the task `[x]`, and append a one-line note under it (what you did / any follow-up discovered — add the follow-up as a new unchecked task if needed).
6. Commit: `git add -A && git commit` with a concise message describing the one task. Do not push.
7. Stop. The loop will restart you for the next task.

## Definition of done (per task)

A task is not done until all of these pass for the code you touched:

- `vendor/bin/sail artisan test --compact` (the relevant filter/file) is green. Add or update a test for the change — every change must be programmatically tested. This repo uses **Pest**.
- `vendor/bin/sail bin pint --dirty --format agent` is clean (run it; it auto-fixes).
- No new TypeScript/ESLint errors if you touched `resources/js`.
- **`code-reviewer` finds no blocker** on the diff. For anything touching `app/Screening`, a prompt, applicant data, or model output, **`fair-housing-auditor` returns PASS** — a protected-class/PII leak is an automatic fail that outranks every other consideration.
- Track **S** (prompt-tuning) tasks are additionally judged by the `screening:eval-prompt` scorecard per the discipline in `ralph.md` / the `screening-eval` skill (one hypothesis, one change, revert if the pass set regresses).

## Rules

- **One task per iteration.** This is the whole point of the loop — breadth is the loop's job, not yours.
- Follow `CLAUDE.md` (Sail-prefixed commands, Laravel conventions, search-docs before changes).
- If a task is ambiguous or blocked, do **not** guess destructively. Mark it `[blocked]` in `ralph.md` with a one-line reason, pick the next task, and continue.
- Never delete tests, never force-push, never change dependencies without it being an explicit task.
- Prefer small, reversible commits.
