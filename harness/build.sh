#!/usr/bin/env bash
#
# build.sh — drive the harness/BUILD.md backlog unattended (orchestrator loop).
#
# Each iteration runs ONE fresh Claude Code lead agent that follows
# harness/BUILD-PROMPT.md: it reads harness/BUILD.md, takes the single most
# important task, DELEGATES the work to the specialist subagents in
# .claude/agents/, verifies (code-reviewer + fair-housing-auditor), checks the
# task off, commits locally (never pushes), and stops. This script restarts it
# until the agent prints HARNESS-DONE (backlog empty) or a safety limit is hit.
#
# Usage:
#   ./harness/build.sh [max_iterations]      # default 25
#
# Notes:
#   - Runs with --dangerously-skip-permissions so the agent can edit files and
#     run Sail/git without prompting. Only run on a branch you're happy to let
#     it commit to (this harness ships on `harness/agent-build`). Review after.
#   - Does NOT push. When done, review `git log` and push yourself.
#   - This is the BUILD loop. The screening prompt-tuning loop is ./ralph.sh.
#   - Per-iteration transcripts: storage/logs/harness/ (git-ignored).

set -uo pipefail

# Operate from the repo root (parent of this script's harness/ dir).
cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit 1

MAX_ITERS="${1:-25}"
PROMPT='Follow harness/BUILD-PROMPT.md — run exactly one iteration, then stop.'
LOG_DIR="storage/logs/harness"
DONE_MARKER='HARNESS-DONE'

mkdir -p "$LOG_DIR"

if ! command -v claude >/dev/null 2>&1; then
    echo "error: 'claude' CLI not found on PATH." >&2
    exit 127
fi
if [[ ! -f harness/BUILD-PROMPT.md || ! -f harness/BUILD.md ]]; then
    echo "error: run from a repo containing harness/BUILD-PROMPT.md and harness/BUILD.md." >&2
    exit 1
fi

echo "Build harness — up to ${MAX_ITERS} iteration(s). Transcripts: ${LOG_DIR}/"
echo "Press Ctrl-C to stop after the current iteration finishes."

stale=0
for (( i = 1; i <= MAX_ITERS; i++ )); do
    log="${LOG_DIR}/iter-$(printf '%03d' "$i")-$(date +%Y%m%d-%H%M%S).log"
    echo ""
    echo "════════ iteration ${i}/${MAX_ITERS} — $(date '+%H:%M:%S') ════════"

    before=$(git rev-parse HEAD 2>/dev/null || echo none)

    claude -p "$PROMPT" --dangerously-skip-permissions 2>&1 | tee "$log"

    if grep -q "$DONE_MARKER" "$log"; then
        echo ""
        echo "✅ ${DONE_MARKER} after ${i} iteration(s) — harness/BUILD.md is complete."
        echo "   Review with: git log --oneline   then push when you're happy."
        exit 0
    fi

    after=$(git rev-parse HEAD 2>/dev/null || echo none)
    if [[ "$before" == "$after" ]]; then
        stale=$(( stale + 1 ))
        echo "⚠️  iteration ${i} made no commit (stale=${stale})."
        if (( stale >= 2 )); then
            echo "⛔ Two iterations in a row with no progress and no ${DONE_MARKER}."
            echo "   Likely blocked — inspect ${log} and harness/BUILD.md, then re-run."
            exit 1
        fi
    else
        stale=0
        echo "→ $(git log -1 --oneline)"
    fi

    sleep 2
done

echo ""
echo "⏹  Hit the ${MAX_ITERS}-iteration cap without ${DONE_MARKER}."
echo "   Progress is committed; just re-run ./harness/build.sh to continue."
exit 1
