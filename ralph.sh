#!/usr/bin/env bash
#
# ralph.sh — drive the PROMPT.md "Ralph" loop unattended.
#
# Each iteration runs ONE fresh Claude Code agent that follows PROMPT.md:
# it reads fix_plan.md, does the single most important task, verifies it,
# checks it off, commits (locally — never pushes), and stops. This script
# just keeps restarting it until the agent prints RALPH-DONE (every task
# checked off) or a safety limit is hit.
#
# Usage:
#   ./ralph.sh [max_iterations]      # default 25
#
# Notes:
#   - Runs the agent with --dangerously-skip-permissions so it can edit
#     files and run Sail/git without stopping to ask. Only run this on a
#     branch you're happy to let it commit to; review the commits after.
#   - The loop does NOT push. When it finishes, review `git log` and push
#     yourself.
#   - Per-iteration transcripts are saved under storage/logs/ralph/
#     (git-ignored), so you can read what each iteration did.

set -uo pipefail

# Always operate from the repo root (where PROMPT.md / fix_plan.md live).
cd "$(dirname "${BASH_SOURCE[0]}")" || exit 1

MAX_ITERS="${1:-25}"
PROMPT='Follow PROMPT.md — run exactly one iteration, then stop.'
LOG_DIR="storage/logs/ralph"
DONE_MARKER='RALPH-DONE'

mkdir -p "$LOG_DIR"

if ! command -v claude >/dev/null 2>&1; then
    echo "error: 'claude' CLI not found on PATH." >&2
    exit 127
fi
if [[ ! -f PROMPT.md || ! -f fix_plan.md ]]; then
    echo "error: run this from a repo containing PROMPT.md and fix_plan.md." >&2
    exit 1
fi

echo "Ralph loop — up to ${MAX_ITERS} iteration(s). Transcripts: ${LOG_DIR}/"
echo "Press Ctrl-C to stop after the current iteration finishes."

stale=0
for (( i = 1; i <= MAX_ITERS; i++ )); do
    log="${LOG_DIR}/iter-$(printf '%03d' "$i")-$(date +%Y%m%d-%H%M%S).log"
    echo ""
    echo "════════ iteration ${i}/${MAX_ITERS} — $(date '+%H:%M:%S') ════════"

    before=$(git rev-parse HEAD 2>/dev/null || echo none)

    # One fresh agent = one task. Stream output to console and to the log.
    claude -p "$PROMPT" --dangerously-skip-permissions 2>&1 | tee "$log"

    if grep -q "$DONE_MARKER" "$log"; then
        echo ""
        echo "✅ ${DONE_MARKER} after ${i} iteration(s) — fix_plan.md is complete."
        echo "   Review with: git log --oneline   then push when you're happy."
        exit 0
    fi

    after=$(git rev-parse HEAD 2>/dev/null || echo none)
    if [[ "$before" == "$after" ]]; then
        stale=$(( stale + 1 ))
        echo "⚠️  iteration ${i} made no commit (stale=${stale})."
        if (( stale >= 2 )); then
            echo "⛔ Two iterations in a row with no progress and no ${DONE_MARKER}."
            echo "   Likely blocked — inspect ${log} and fix_plan.md, then re-run."
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
echo "   Progress is committed; just re-run ./ralph.sh to continue."
exit 1
