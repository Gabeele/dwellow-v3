# harness/ — the dwellow-v3 build harness

An **orchestrator-worker** harness that builds out the roadmap autonomously, layered on
top of the existing single-agent tooling. There are two loops in this repo — keep them
straight:

| Loop | Driver | Standing prompt | Backlog | Purpose |
| --- | --- | --- | --- | --- |
| **Build** | `harness/build.sh` | `harness/BUILD-PROMPT.md` | `harness/BUILD.md` | Build features + new product agents across the roadmap |
| **Tuning** | `ralph.sh` | `PROMPT.md` | `ralph.md` | Hill-climb the screening prompt only |

## How the build loop works
`build.sh` restarts a fresh `claude -p` **lead agent** each iteration. The lead reads
`BUILD.md`, takes the single most important task, and **delegates** to specialist
subagents in `.claude/agents/` rather than doing everything itself:

- `agent-engine-builder` — new `laravel/ai` product agent types (uses the `agent-engine` skill)
- `laravel-implementer` / `inertia-vue-implementer` — backend / frontend
- `test-author` — coverage · `prompt-tuner` — screening prompt rounds (uses `screening-eval`)
- `code-reviewer` + `fair-housing-auditor` — adversarial verification before commit
- `docs-scribe` — ADRs, feature docs, backlog, delta log

Every task ends green (tests + pint) and is committed locally. The loop **never pushes**.

## Running it
```bash
# one supervised goal at a time (recommended first):
claude -p "Follow harness/BUILD-PROMPT.md — run exactly one iteration, then stop." \
  --dangerously-skip-permissions

# or the full unattended loop, capped:
./harness/build.sh 25          # up to 25 iterations, or until HARNESS-DONE
```
Transcripts land in `storage/logs/harness/` (git-ignored). Review with `git log` and push
yourself when happy.

## Safety
- Runs with `--dangerously-skip-permissions`; only run on a branch you're happy to let it
  commit to. This harness ships on `harness/agent-build`.
- The dev loop scores against **Ollama** — it never hits a paid provider. Tasks that need a
  provider decision or new product scope are marked `[deferred — needs spec]` in `BUILD.md`
  and are intentionally not auto-built.
