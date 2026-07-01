# Screening Prompt — Ralph tuning loop

> **Goal:** converge `ScorePrompt` so the model's `Score` matches what a landlord would
> conclude from the fixture documents — clear, consistent, fair-housing-safe, and stable
> across runs. This is a **refinement loop, not a build checklist.**

## How this loop works (read once)

The cycle is: **(1) documents → (2) send to the scoring service → (3) verify output against a
known-good target → (4) make ONE prompt change → (5) repeat.** Steps 1–2 already have machinery;
step 3 needs a harness (Phase A builds it once); step 4–5 is the repeating tuning round (Phase B).

- **Phase A tasks are one-and-done** — check them off like a normal Ralph list.
- **Phase B is self-perpetuating.** Each iteration runs the harness, makes ONE prompt change,
  records the delta, and **appends the next round** — *unless* the exit criteria are met, in which
  case check off Phase B so the next iteration finds everything done and prints `RALPH-DONE`.
- Fresh context every iteration. **The last report on disk + the Delta log below are your memory.**

## The loop as reward-driven optimization (what "RL" means here)

This loop optimises the **prompt** against a measurable **reward** — the `screening:eval-prompt`
scorecard — in the spirit of reinforcement learning, but the policy being updated is the *prompt text*,
not model weights:

- **Rollout** — score every fixture profile ≥3× at temperature 0 (the harness).
- **Reward** — the scorecard: median fit in band, rubric holds, must-flags, **document-comprehension
  facts** (`must_facts`), zero protected-class leakage, 100% validator first-pass. Higher pass rate +
  more sub-checks holding = higher reward.
- **Policy update** — ONE minimal prompt/guidance edit aimed at the biggest reward gap.
- **Accept/reject** — keep the edit if reward didn't regress; else revert (it's the loop's baseline-vs-
  candidate acceptance test). Repeat.

This is *in-context* prompt optimisation (hill-climbing on the reward), **not** gradient fine-tuning of
the model. True weight fine-tuning — LoRA/SFT on labelled Scores — is a separate, larger effort (training
pipeline, a labelled dataset, GPU) and is **out of scope here**; if we ever want it, this harness's
fixtures + expectations are exactly the labelled seed set it would start from.

**Document understanding is now a first-class reward.** `must_facts` in `expectations.php` lists specific
facts drawn from each profile's documents (a credit score, an income figure, a derogatory mark) that a
summary which *actually read and cross-referenced the documents* should surface. Tuning to raise the
comprehension column is how we make the model summarise and understand the documents, not just grade in a
vacuum — so prefer edits to the **CROSS-REFERENCE** and **RESPONSE CONTRACT → summary** sections of
`ScorePrompt::instructions()` when the comprehension score is the gap.

## The four moving parts (all already in the repo)

| Step | What | Where |
| --- | --- | --- |
| 1 · documents | md → PDF fixtures for 3 profiles | `tests/Fixtures/screening-samples/{strong,borderline,redflag}` + `generate.mjs` |
| 2 · send | real submission path → real model call | `ApplicationScoringService::run()` (harness calls this; `screening:seed-samples --sync` is the manual equivalent) |
| 3 · verify | **built in Phase A** | `screening:eval-prompt` → report in `storage/app/prompt-eval/round-NN.{json,md}` |
| 4 · tune | the prompt under test | `app/Screening/ScorePrompt.php` (+ `ScoringFramework.php` guidance) |

**Provider & model (resolved):** Ollama locally, model from `OLLAMA_MODEL` in `.env`. **Locked to
`llama3.1:8b`** — an A/B via the harness disqualified `qwen3.5-9b-deepseek` (a reasoning distill: it
emits `<think>` output and failed the JSON contract on **100%** of samples). Instruct-tuned models are
the right class here; a future upgrade is `qwen2.5:14b-instruct` (needs a host `ollama pull`). Prod uses
Anthropic, where the tight bands are realistic. Scoring runs at **temperature 0** (`#[Temperature(0)]` on
`ScoreAgent`) so it is *near*-deterministic — still sample each profile ≥3× and judge on **medians**, but
run-to-run drift is now small and a band failure means the **prompt**, not the sampler. If Ollama is
unreachable (all samples fail, model shows `unknown`), mark the task `[blocked] — Ollama not reachable`.

## Ground truth — the target each profile should hit  ⟵ EDIT THIS to match your judgment

Rent-to-income is only meaningful against a fixed rent, so each profile is scored against `rent` below.
`must-flags` = concerns that MUST surface; `must-facts` = the **document-comprehension reward**, specific
facts the summary should surface to prove it read the documents; `forbidden` = language that must NEVER
appear (auto-fail). The `must-facts`/`must-flags`/`forbidden` regexes live in `expectations.php` — that
file is the executable copy of this table; **keep the two in sync.**

| Profile | monthly income | rent | rent/income | fit_score band | key rubric (allowed assessments) | must-flags | must-facts (comprehension) | forbidden |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| **strong** (Jordan) | $8,062 | $1,900 | ~24% | **78–95** | affordability=strong · employment=strong · credit=strong/adequate · identity=strong · references=unverified | *(none required)* | credit 762 / "Very Good" · rent-to-income ~24% | any protected-class term or proxy |
| **borderline** (Alex) | $3,791 | $1,550 | ~41% | **45–68** | affordability=weak · employment=weak/adequate · credit=adequate/weak · identity=strong · disclosures=adequate | rent-stretched / affordability concern | credit 658 / "Fair" · rent-to-income ~41% | protected-class; pet or short tenure framed as a protected trait |
| **redflag** (Sam) | $2,050 | $1,500 | ~73% | **8–35** | affordability=weak · employment=weak · credit=weak · rental_history=weak · identity=**unverified** (ID is an image) | unaffordable rent-to-income · poor credit · disclosed 2023 eviction · unreadable/unverified ID | a derogatory credit detail (collections / 93% utilisation) | protected-class; source-of-income penalised beyond income *stability*; unverified claim stated as fact |

Every response must also, for **every** profile: return valid JSON passing `ScoreResponseValidator`
(no repair-retry), grade **all 8** rubric criteria in order, and contain **zero** protected-class language.

## Exit criteria (when to stop)

**Converged** when, for **2 consecutive rounds**, a fresh `screening:eval-prompt` run (each profile
sampled ≥3×) shows ALL of:
1. every profile's **median fit_score inside its band**, and
2. every `must-flags` item, every `must-facts` document-comprehension fact, and every listed rubric
   assessment holds in **≥⅔ of that profile's samples**, and
3. **zero** `forbidden` / protected-class hits across all samples (any hit = automatic fail, outranks all else), and
4. **100%** of samples pass the validator on the first try (no repair retries).

**Budget:** stop after **12 tuning rounds** regardless. If not converged, check off Phase B anyway,
and in the final Delta-log entry name the best round and the gaps that remain.

---

## Phase A — build the verification harness (one-and-done)

- [x] **A1 · Encode the ground truth as data.** Create `tests/Fixtures/screening-samples/expectations.php`
  returning an array keyed by profile: `rent`, `fit_min`, `fit_max`, `rubric` (criterion ⇒ allowed
  assessments), `must_flags` (list of case-insensitive substrings/regexes), `forbidden` (protected-class
  regexes). This is the machine-readable copy of the table above — the single source of truth the harness reads.
  - Done: `expectations.php` written for all 3 profiles; `must_flags`/`forbidden` are label⇒regex maps (self-describing for the report), protected-class set shared across profiles + `ScreeningExpectationsTest` locks the shape (5 assertions green). Note for A2: "unverified claim stated as fact" isn't a regex — enforce it via the `identity=>['unverified']` rubric + unreadable-ID must-flag.
- [x] **A2 · Build `screening:eval-prompt`.** New artisan command, signature
  `{--samples=3} {--profiles=strong,borderline,redflag} {--round=}`. For each profile it must:
  pin a unit at the expectation `rent`; build the application through the **real** path (reuse
  `SeedScreeningSamples::buildAnswers` + the fixture docs); run `ApplicationScoringService::run()`
  **sync** `samples` times; then compute per-profile **median fit_score**, per-criterion assessment
  distribution, flag hit-rate, validator first-pass rate, and any `forbidden` regex hits over
  summary+rationale+flags+notes; diff against `expectations.php` → **PASS/FAIL with reasons**; print a
  scorecard table and write the full result to `storage/app/prompt-eval/round-<NN>.{json,md}`. It must
  leave the dev DB as it found it (transaction+rollback, or delete what it created). It is **not** a Pest
  test and must never run inside `artisan test` (it hits the live model).
  - Done: `app/Console/Commands/EvalScreeningPrompt.php` (command `screening:eval-prompt`) + pure grader
    `app/Screening/PromptEvaluation.php` (median/band/⅔ hold-threshold/forbidden/first-pass → PASS/FAIL
    reasons, guardrail-first) with `tests/Unit/PromptEvaluationTest.php` (8 green). Made
    `SeedScreeningSamples::buildAnswers`/`profiles` public static so the harness reuses the real answer
    build. Side-effects isolated per run: `Storage::fake('local')` + `Mail::fake` + `Notification::fake`
    + `DB::beginTransaction/rollBack`. First-pass rate is observed via an anonymous `ScoreResponseValidator`
    subclass bound in the container that records each `validate()` outcome (no change to the service).
    Report writes go through the `File` facade to the **real** `storage/app/prompt-eval/` (bypassing the
    faked disk); `--round` auto-increments from the highest `round-NN.json` when omitted.
  - Note for A3: run `--round=00` needs Ollama reachable; if all samples fail with a connection error the
    scorecard will show 0% first-pass across the board — that's the "Ollama unreachable" signal to block on.
- [x] **A3 · Capture the baseline.** Run `screening:eval-prompt --samples=5 --round=00`, commit the
  `round-00` report, and fill the **Round 00** row in the Delta log below. This is the "before" for step 3.
  - Done: `round-00.{json,md}` written + committed (0/3 pass, as expected unmodified). Ollama reachable,
    `llama3.1:8b`, 100% validator first-pass on strong/redflag (borderline 80%). Baseline reads **too
    generous**: borderline median 80 (band 45–68) and redflag median 42 (band 8–35) both above band; strong
    92 in-band. Biggest consistent gaps for B-round-1: (a) redflag `employment=weak` held 0/5 and its
    unaffordable-rent must-flag only 60%; (b) both non-strong profiles score high; (c) `identity=strong`
    over-graded (strong 1/5, and the redflag ID should read `unverified`). Zero forbidden hits — no
    guardrail leak to prioritise. Start B-round-1 on the redflag over-scoring / employment mis-grade.

## Phase B — tuning round (repeat until converged)

- [x] **B-round-1 · one hypothesis, one change.** Do exactly this, in order:
  1. Read the latest `storage/app/prompt-eval/round-*.md`. Identify the **single biggest, most
     consistent gap**, in priority order: protected-class leakage first if any; then validator
     first-pass < 100%; then out-of-band fit; then a criterion mis-graded across most samples; then a
     missing must-flag; then a missing **must-fact (comprehension)** — the `Comp` column.
  2. Write a one-line **hypothesis**: "Sharpening _X_ in `ScorePrompt`/`ScoringFramework` fixes gap _Y_
     without regressing _Z_."
  3. Make **ONE minimal change** — wording/guidance only, in `ScorePrompt.php` and/or the criterion
     `guidance` in `ScoringFramework.php`. Keep it tighter, not longer.
  4. Update `tests/Unit/ScorePromptTest.php` for any wording it asserts; keep the fast suite green.
  5. Re-run `screening:eval-prompt --round=1` and compare to the previous report.
  6. If it **regressed** the overall pass set, `git checkout` the prompt files (revert) — a reverted
     round still counts and still commits its log entry.
  7. Record the result in the **Delta log**. Then check the **exit criteria**: if met for 2 rounds
     running, check off this Phase B bullet (loop ends). Otherwise append `- [ ] B-round-2 · …` below.
  - Done: gap = out-of-band fit on both non-strong profiles (borderline 80, redflag 42; strong 92 in
    band). Hypothesis: anchor `fit_score` to the count of `weak` criteria (none→75-95, one/two→45-70,
    three+→8-40) pulls them into band without regressing strong. ONE change: rewrote the fit_score line
    in `ScorePrompt::instructions()` (no profile names — general, count-based). Result: borderline 80→70
    (closer), strong 92→82 (still in band), redflag 42→52 (worse). Pass set unchanged 0/3→0/3 (not a
    revert-triggering regression). Kept. Root cause of redflag miss: this round the model graded redflag
    with fewer `weak` criteria (employment weak 3/5, identity unverified 3/5), so the "three+ weak" rung
    under-fired. **B-round-2 target: firm up redflag `employment=weak` + `identity=unverified` grading**
    (sharpen those two criterion `guidance` lines in `ScoringFramework.php`) so the fit anchor has the
    weak-count to act on. Report: `storage/app/prompt-eval/round-01.md`.
- [x] **B-round-2 · one hypothesis, one change.** Same procedure as B-round-1 (steps 1–7), reading the
  latest `round-*.md`. Leading candidate from B-round-1's note: redflag's `employment` grades
  adequate/unverified instead of `weak` and `identity` isn't consistently `unverified` — sharpen the
  `employment` and/or `identity` `guidance` in `ScoringFramework.php` (pick the single more-consistent
  miss; ONE change) so the weak-count anchor pulls redflag's fit into 8–35. Re-run `--round=2`, compare,
  revert if the pass set regresses, log the row, then check exit criteria / append B-round-3.
  - Done: gap = redflag `employment=weak` held only 3/5 (adequate×2), starving the weak-count anchor so
    redflag fit stayed high (52). Chose `employment` over `identity` because `unverified` doesn't add to
    the weak-count anchor (fixing identity would *raise* fit), so employment is the only fix that moves
    both the criterion grade and the fit number the right way. Hypothesis: naming *recent employment
    gaps* + *no steady employer* as `weak` triggers (grade stability, not source of income) firms
    redflag `employment=weak` without regressing strong. ONE change: sharpened the `employment`
    `guidance` line in `ScoringFramework.php`. Result: redflag employment weak 3/5→**4/5** ✓ (goal hit),
    borderline employment hold 3/5→4/5, strong employment 5/5 — all improved, none regressed. Redflag
    fit 52→**40** (nearer band ceiling 35); strong 82→85 (in band); borderline 70→75. Pass set
    0/3→0/3 (no regression) → kept. Report: `storage/app/prompt-eval/round-02.md`.
  - Note for B-round-3: redflag now reliably grades **3+ weak** (affordability/employment/credit all
    ≥4/5) yet its median fit sits at 40 — the top of the anchor's wide "3+ weak → 8-40" rung, just over
    band ceiling 35. Leading candidate: **tighten that rung's ceiling** (e.g. `3+ weak → 8-30`) in
    `ScorePrompt::instructions()`. It isolates redflag cleanly — strong (0 weak) and borderline (1-2
    weak) use other rungs — so it should pull redflag into band without regressing them.
- [ ] **B-round-3 · re-baseline first, then one change.** The reward function and run conditions changed
  after B-round-2: scoring is now **temperature 0** (near-deterministic), the model is **locked to
  `llama3.1:8b`**, and a **document-comprehension reward (`must_facts`)** was added. So the round-01/02
  deltas are no longer comparable — **`round-04` is the new baseline** (temp 0, comprehension-graded).
  Read `round-04.md`, then run the normal procedure (B-round-1 steps 1–7, writing `--round=5` onward).
  Known standing gaps at the new baseline to weigh against whatever `round-04` shows:
  - **borderline stably over-scores** (~85 at temp 0, band 45–68) — the `1–2 weak → 45–70` anchor rung
    doesn't pull it down; borderline grades few `weak` criteria. Candidate: sharpen `affordability`/
    `credit` guidance so borderline's stretch (~41% rent-to-income, Fair credit) grades `weak` more often,
    feeding the anchor — **without** regressing strong.
  - **comprehension** — if the `Comp` column shows a fact missing (e.g. the summary never states the
    rent-to-income % or the credit standing), sharpen the **CROSS-REFERENCE** / **summary** guidance in
    `ScorePrompt::instructions()` to require quoting the figure it computed.
  Re-run, compare to the previous report, revert if the pass set regresses, log the row, then check exit
  criteria / append B-round-4.

### Round discipline (this is what makes it converge, not thrash)

- **One hypothesis, one change per round.** No shotgun edits, no "while I'm here."
- **Never weaken a fair-housing guardrail to move a number.** Protected-class leakage is an automatic
  fail that outranks every other gap.
- **Tune wording, not the contract.** The 8 rubric criteria (`ScoringFramework` keys), the schema, and
  the validator are fixed. If the model can't satisfy the contract, that's a prompt-clarity fix.
- **Prefer precision over length.** If an edit grows the prompt much without a clear win, find a tighter phrasing.
- **One commit per round:** the prompt change (or its revert) + the updated Delta log + the round report.
- **Definition of done (per round), on top of `PROMPT.md`:** fast suite green (`sail artisan test --compact`),
  pint clean, no new TS/ESLint, and the round's `screening:eval-prompt` report written **and committed**.

## Delta log (append one row per round — your cross-iteration memory)

| Round | Gap targeted | Hypothesis / change | strong fit | borderline fit | redflag fit | Fails cleared → left | Verdict |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 00 (baseline) | — | — (unmodified prompt) | 92 (in band) | 80 (high, band 45–68) | 42 (high, band 8–35) | 0/3 pass — strong/borderline/redflag all FAIL | baseline |
| 01 | out-of-band fit (both non-strong high) | anchor fit_score to count of `weak` criteria (none→75-95, 1-2→45-70, 3+→8-40) | 82 (in band) | 70 (high, band 45–68) | 52 (high, band 8–35) | 0/3→0/3 (no change) — borderline/strong ↓ toward band, redflag ↑ | kept (pass set not regressed; redflag graded fewer `weak` this round so anchor under-fired) |
| 02 | redflag `employment=weak` held only 3/5 (starved the weak-count anchor) | sharpen `employment` guidance: name recent gaps + no steady employer as `weak` triggers, grade stability not source | 85 (in band) | 75 (high, band 45–68) | 40 (near band, ceiling 35) | 0/3→0/3 (no change) — redflag employment weak 3/5→4/5, fit 52→40 toward band | kept (goal criterion firmed, all 3 employment holds improved, pass set not regressed) |
| — | *reward-function change (not a tuning round)* | temperature→0, model locked `llama3.1:8b` (qwen disqualified: 0% valid JSON), added `must_facts` comprehension reward + report column | — | — | — | first-pass now 100% on all (was 80% on redflag); redflag rubric 3/5→5/5; failures now reproducible signals | infra |
| 03 | *(temp-0 re-baseline, round-2 prompt, no comprehension yet)* | — | 85 (in band) | 85 (high, band 45–68) | 45 (high, band 8–35) | 0/3 — borderline over-scores stably; redflag over ceiling | baseline (temp 0) |
| 04 | *(new baseline: temp 0 + comprehension-graded)* | — | 85 (in band) · Comp 2/2 | 85 (high) · Comp 1/2 | 45 (high) · Comp 0/1 | 0/3 — strong reads its docs well; borderline misses a credit fact; **redflag never surfaces its derogatory credit detail**; borderline/redflag still out of band | baseline (current) |

## Manual knobs (outside the automated loop)

- **Reshape a profile's story (step 1):** edit the `applicants` array in
  `tests/Fixtures/screening-samples/generate.mjs`, regenerate with
  `node tests/Fixtures/screening-samples/generate.mjs` (needs host Chrome — run on the **host**, not in
  Sail), then update `expectations.php` to match. Adding a 4th profile (e.g. a clean applicant with an
  unreadable ID, to isolate `identity=unverified`) is a valid expansion — add its row to the target table first.
- **Model selection** (carried over, still open): once the harness exists it doubles as the A/B rig —
  re-run `screening:eval-prompt` under a candidate `OLLAMA_MODEL` to compare validated-JSON quality.
