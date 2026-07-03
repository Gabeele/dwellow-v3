---
name: screening-eval
description: "ACTIVATE when running or interpreting dwellow-v3's screening prompt-evaluation harness (screening:eval-prompt) — measuring the scorer against fixture profiles, reading round-NN scorecards, or picking the next tuning target. Use with the prompt-tuner subagent. Do NOT activate for building a new agent type (use agent-engine)."
license: MIT
metadata:
  author: gavin
---

# Screening Eval Harness (dwellow-v3)

The scorer is optimised against a measurable reward, not vibes. This skill covers running the harness and reading its output. Prompt changes themselves follow the round discipline in `ralph.md`.

## The moving parts
| Part | Where |
| --- | --- |
| Fixture documents (3 profiles) | `tests/Fixtures/screening-samples/{strong,borderline,redflag}/` (md → PDF via `generate.mjs`) |
| Ground truth (executable) | `tests/Fixtures/screening-samples/expectations.php` |
| The run + scorecard | `php artisan screening:eval-prompt` → `storage/app/prompt-eval/round-NN.{json,md}` |
| Pure grader | `app/Screening/PromptEvaluation.php` |
| The prompt under test | `app/Screening/ScorePrompt.php` (+ `ScoringFramework.php` guidance) |

## Running it
```bash
vendor/bin/sail artisan screening:eval-prompt --samples=3 --profiles=strong,borderline,redflag --round=<NN>
```
- Scores each profile `--samples` times at temperature 0, medians the results, isolates all side effects (fake disk/mail/notifications + DB transaction rollback), and writes a JSON + Markdown report. `--round` auto-increments when omitted.
- It hits the **live** model (Ollama dev). It is **not** a Pest test — never run it under `artisan test`.
- If every sample fails and the model shows `unknown`/0% first-pass, Ollama is unreachable → treat as `[blocked]`, not a prompt failure.

## Reading a scorecard
Per profile the report grades, guardrail-first:
1. **forbidden** (protected-class regex hits) — any hit = automatic fail, outranks everything.
2. **validator first-pass rate** — should be 100% (no repair retries).
3. **median fit_score in band** — `[fit_min, fit_max]` from `expectations.php`.
4. **rubric assessments** — each criterion's allowed assessments hold in ≥⅔ of samples.
5. **must_flags** — required concerns surface (regex over summary/rationale/flags/notes).
6. **must_facts** — document-comprehension facts surface (proves the model read/cross-referenced the docs).

## Converged (when to stop)
For **2 consecutive rounds**: every profile's median fit in band, every must-flag / must-fact / listed rubric assessment holds in ≥⅔ of samples, **zero** forbidden hits, and **100%** validator first-pass. Budget: 12 rounds max.

## Reshaping fixtures (manual, outside the loop)
Edit the `applicants` array in `generate.mjs`, regenerate on the **host** (`node tests/Fixtures/screening-samples/generate.mjs`, needs host Chrome — not Sail), then update `expectations.php` to match. Keep the ground-truth table in `ralph.md` in sync.
