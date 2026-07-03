# Build Harness — Backlog

The task list for the `harness/build.sh` loop. One task per iteration; the lead agent
delegates to the `.claude/agents/` subagents (see `harness-orchestration` skill).
Grounded in `.docs/roadmap.md` and `.docs/open-questions.md`.

**Ordering principle (from the roadmap):** *don't broaden until screening is genuinely
loved.* So: harden the harness → converge & deepen screening → add the first new product
agent → only then touch lifecycle expansion. Tasks marked `[deferred — needs spec]` are
enumerated for completeness but must NOT be auto-built; they need an ADR / spec-writer
issue and a human decision first. Skip them and keep going.

Legend: `[ ]` actionable · `[x]` done · `[blocked] — reason` · `[deferred — needs spec]`.

---

## Phase 0 — Harden the harness (do first; the loop must be able to trust itself)

- [ ] **P0.1 · Prove the loop wiring.** Confirm each `.claude/agents/*.md` subagent loads
  and each new skill is discoverable; run one delegated no-op task end-to-end (e.g. have
  `docs-scribe` fix a typo) to prove delegate → verify → commit works. Record the result
  as a note here.
- [ ] **P0.2 · Loop permissions.** Add the Bash/artisan/git/gh commands the loop needs to
  `.claude/settings.local.json` `allow` (mirror what `ralph` already lists) so iterations
  don't stall. No secrets.
- [ ] **P0.3 · Ignore harness logs.** Ensure `storage/logs/harness/` is git-ignored (as
  `storage/logs/ralph/` is).
- [ ] **P0.4 · Wire CI awareness.** Confirm the fast suite + pint + eslint commands in the
  Definition of Done match this repo's actual scripts (`composer.json`, `package.json`);
  fix the harness docs if they drifted.

## Phase 1 — Converge & deepen screening (the wedge; make it best-in-class)

- [ ] **P1.1 · Converge the screening prompt.** Run `prompt-tuner` rounds from the current
  `ralph.md` baseline (round-04) until the exit criteria hold for 2 consecutive rounds or
  the 12-round budget is spent. One hypothesis / one change / one commit per round; log
  each in the `ralph.md` Delta log. (Requires Ollama.)
- [ ] **P1.2 · Fair-housing audit trail.** Persist and expose the model's rationale + rubric
  per Score for landlord review and compliance (it is already stored on `Agent`/`Score` —
  surface it and confirm it's auditable). `fair-housing-auditor` must PASS.
- [ ] **P1.3 · Sort/filter applications by score.** Landlord application list can sort and
  filter by `fit_score` and status. (Deferred item in open-questions — now in scope.)
- [ ] **P1.4 · Portfolio-wide applicant overview.** A cross-property/-unit view comparing
  applicants (the roadmap's compare/contrast dashboard). Backend + Inertia/Vue.
- [ ] **P1.5 · Decision notifications wording.** Finalize approve/reject applicant email
  copy + trigger; `fair-housing-auditor` reviews the copy. (Open question — confirm wording
  is neutral and non-protected-class.)
- [ ] **P1.6 · Manual re-score action.** A landlord-triggered "re-run analysis" that reuses
  the 1:1 Agent (idempotent) and records an Activity.

## Phase 2 — New product AI agents (extend the engine via agent-engine-builder)

- [ ] **P2.1 · Application-summary agent.** A second `AgentType` that produces a concise,
  neutral, cross-application comparison/summary to power P1.4 — the smallest real second
  agent, proving the polymorphic engine generalises. Follow the `agent-engine` skill;
  `fair-housing-auditor` PASS required.
- [ ] **P2.2 · Reference-check agent.** `AgentType` that drafts reference-request questions
  and summarises returned reference responses into a structured, neutral assessment
  (roadmap "automated references"). Reasons over people → guardrails + auditor mandatory.
  Depends on a references data model — if that model doesn't exist yet, mark
  `[blocked — needs references model]` and add building it as a `laravel-implementer` task.

## Phase 3 — Lifecycle expansion (NORTH STAR — gated; do not auto-build)

- [ ] **P3.1 · Lease & onboarding** — convert an approved applicant into a signed tenant.
  `[deferred — needs spec]` (ADR + spec-writer issue first).
- [ ] **P3.2 · Rent collection** — online payments, reminders, late fees.
  `[deferred — needs spec]`.
- [ ] **P3.3 · Maintenance requests + triage agent** — tenant requests, an `AgentType` that
  triages/prioritises them. `[deferred — needs spec]` (new domain; large).
- [ ] **P3.4 · Accounting** — per-property income/expense, tax-ready reports.
  `[deferred — needs spec]`.
- [ ] **P3.5 · Subscription billing** — activate the landlord subscription (Stripe/Cashier).
  `[deferred — needs spec]`.

---

## Notes / discovered follow-ups (append as the loop learns)

- _(loop appends one-liners here per iteration)_
