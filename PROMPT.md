# Ralph Loop — Standing Instructions

You are running in a loop. Each iteration is a fresh context; **the filesystem and git are your only memory.** `ralph.md` is the source of truth for what's left to do.

## Each iteration, do exactly this:

1. Read `ralph.md`. Pick the **single most important** unchecked task (top of the list unless something is now more urgent).
2. If every task is checked, output `RALPH-DONE` and stop. Do nothing else.
3. Implement **only that one task.** Do not start, scope-creep into, or "while I'm here" any other task.
4. Verify it (see Definition of done). If verification fails, fix it within this same iteration before moving on.
5. Update `ralph.md`: check off the task `[x]`, and append a one-line note under it (what you did / any follow-up discovered — add the follow-up as a new unchecked task if needed).
6. Commit: `git add -A && git commit` with a concise message describing the one task. Do not push.
7. Stop. The loop will restart you for the next task.

## Definition of done (per task)

A task is not done until all of these pass for the code you touched:

- `vendor/bin/sail artisan test --compact` (the relevant filter/file) is green. Add or update a test for the change — every change must be programmatically tested.
- `vendor/bin/sail bin pint --dirty --format agent` is clean (run it; it auto-fixes).
- No new TypeScript/ESLint errors if you touched `resources/js`.

## Rules

- **One task per iteration.** This is the whole point of the loop — breadth is the loop's job, not yours.
- Follow `CLAUDE.md` (Sail-prefixed commands, Laravel conventions, search-docs before changes).
- If a task is ambiguous or blocked, do **not** guess destructively. Mark it `[blocked]` in `ralph.md` with a one-line reason, pick the next task, and continue.
- Never delete tests, never force-push, never change dependencies without it being an explicit task.
- Prefer small, reversible commits.
