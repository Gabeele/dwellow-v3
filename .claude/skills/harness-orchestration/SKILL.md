---
name: harness-orchestration
description: "ACTIVATE when running or extending the dwellow-v3 Ralph loop — the autonomous claude -p loop (ralph.sh + PROMPT.md over the ralph.md backlog) that picks one task per iteration, optionally delegates to the .claude/agents subagents, verifies, and commits. Explains the tracks (coding, context, prompt-tuning) and the per-track definition of done. Read this before driving the loop or editing ralph.md."
license: MIT
metadata:
  author: gavin
---

# Ralph Loop Orchestration (dwellow-v3)

One loop, one backlog, multiple **tracks**. `ralph.sh` restarts a fresh Claude Code agent each
iteration; the agent reads `ralph.md`, does the single most important task, verifies it, checks it
off, commits locally, and stops. **The filesystem + git are the only memory between iterations** —
`ralph.md` is the source of truth for what's left.

## Read first, every iteration
`CONTEXT.md` (what dwellow is + architecture) → `CLAUDE.md` (coding rules) → `ralph.md` (the backlog).
Activate the skill for the domain you're touching (don't wait until stuck).

## The tracks (a task is tagged with one)
| Track | What | Reward / definition of done | Delegate to |
| --- | --- | --- | --- |
| **C · Coding / feature** | build or change app behaviour | tests green + pint clean + `code-reviewer` PASS (+ `fair-housing-auditor` if it touches screening/people) | `laravel-implementer`, `inertia-vue-implementer`, `test-author`, `filament-development` skill, `agent-engine-builder` (new AI agent types) |
| **X · Context / harness** | keep `CONTEXT.md`, `.docs/`, ADRs, skills, backlog true | doc matches code; ADR recorded; backlog honest | `docs-scribe` |
| **S · Screening prompt-tuning** | hill-climb `ScorePrompt` against the eval | the `screening:eval-prompt` scorecard (bands, must-facts, guardrails, first-pass); reward-driven, one hypothesis/one change per round | `prompt-tuner` (uses `screening-eval` skill) |

## Each iteration (the procedure)
1. Read `ralph.md`; pick the **single most important** unchecked, non-`[blocked]`/`[deferred]` task
   (top of its section unless something is now more urgent). If none remain, print `RALPH-DONE`, stop.
2. **Do only that one task.** No scope-creep. You are allowed — encouraged — to **delegate** via the
   Task tool to the subagent that fits (see table). For a full-stack task, split the slices and
   integrate. Track S tasks follow the tuning discipline in `ralph.md` (one change, revert if regressed).
3. **Verify against the track's definition of done.** Run `code-reviewer` on the diff; run
   `fair-housing-auditor` for anything touching `app/Screening`, prompts, applicant data, or model
   output. A guardrail failure is a blocker that outranks everything — fix before proceeding.
4. Update `ralph.md`: check off `[x]`, append a one-line note (what you did + any follow-up as a new
   unchecked task in the right track).
5. `git add -A && git commit` with a concise message for the one task. **Do not push.**
6. Stop — the loop restarts you.

## Rules
- **One task per iteration.** Breadth is the loop's job, not yours.
- Follow `CLAUDE.md` (Sail-prefixed commands, `search-docs` before changes). Small, reversible commits.
- If a task is ambiguous, needs a product decision, or is blocked, mark it `[blocked] — <reason>`
  (or leave `[deferred — needs spec]`), pick the next, continue. Don't guess destructively on
  undecided product scope.
- **Provider safety:** the dev loop scores against **Ollama**; never wire a paid provider or hardcode
  a model. If an agent end-to-end check needs Ollama and it's down, mark `[blocked] — Ollama not reachable`.
- Never delete tests, never force-push, never change dependencies unless the task explicitly says so.

## Running it (must be run by a logged-in human terminal)
`./ralph.sh [max_iters]` runs unattended with `--dangerously-skip-permissions` on the current branch;
review `git log` and push yourself. A `claude -p` spawned from inside another Claude session is **not
logged in** and will fail — start the loop from your own terminal.
