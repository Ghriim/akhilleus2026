# Akhilleus 2026 — v1 Development Plan

## Resume pointer (last session snapshot)

- **Last completed step**: **3.11 — Verification → Phase 3 (Leveling) COMPLETE**. Audit confirmed full Leveling coverage already in place (`LevelingCalculatorTest`, `LevelCurveEvaluatorTest`, all 4 Leveling validators, all 7 admin Leveling UC integration tests, `ListEarnedExperienceUseCaseTest`); the only gap was the 0-minute-workout XP case → added `FinishWorkoutUseCaseTest::testItGrantsNoExperienceForAZeroMinuteWorkout` (`earnedXp === null`, no `EarnedExperience` row; seed helper's `dateStart` parameterised). `composer cs`/`stan` green; in-container phpunit **425 tests / 858 assertions**; both frontends `typecheck`/`lint`/`build` green. **Phases 0–3 are now all `[x]`.** **Uncommitted** (everything 3.1→3.11 + the 3.6 dev-env side fixes is on disk, not yet committed). Dev-env: run Symfony with `--no-tls` (http frontends, no CA).
- **Next pending step**: **4.1 — Questing DataModels + Registries** (start of Phase 4). See the Phase 4 section below. Questing reads tracking metrics (Phase 2) and, on reward claim, creates an `EarnedExperience` (Phase 3) — both dependencies are now in place. Before scaffolding, re-read the Phase-4 plan section and the baked-in decisions (lazy materialization of `QuestProgression`, `QuestProgressionEvaluator::refreshFor`, the `targetValue` decimal-string convention). NB: the dev-plan's table-name text uses `*_data_model` placeholders that are stale (actual tables drop the suffix, e.g. `quest`, `quest_progression`) — match the on-disk Doctrine `#[ORM\Table(name: ...)]` convention used by `earned_experience`/`level_bracket`.
- **Earlier baseline (3.9 — header progress bar)**: `GetPlayerProfileUseCase` + `PlayerProfileDataOutput` + `ProfilePlayerController` (`GET /api/player/profile`); website `<PlayerLevelBadge />` in `components/layout/` mounted in `NavBar`.
- **Earlier baseline (3.8 — Player XP journal)**: `ListEarnedExperienceUseCase` (paginated, `earnedAt DESC`) + `EarnedExperiencePlayerController::journal` (`GET /api/player/leveling/journal`); website `pages/leveling/XpJournalPage.tsx` + flat NavBar link.
- **Earlier baseline (3.7 — Admin LevelingConfig)**: `Get`/`Update LevelingConfigUseCase` under `UseCase/Admin/Leveling/LevelingConfig/`, `UpdateLevelingConfigValidator` (`≥ 50`, code `LEVELING_CONFIG_VALIDATION_FAILED`), `LevelingConfigAdminController` (`GET`/`PUT /api/admin/leveling-config`); admin frontend `LevelingConfigCard` folded into `levelBrackets`. Singleton pre-seeded (3.5) → no migration.
- **Earlier baseline**: **3.5 — workout COMPLETED → `EarnedExperience` generation**. `FinishWorkoutUseCase::awardWorkoutExperience` creates an unlocked `EarnedExperience` (`amount = (int) round(durationMin) × LevelingConfig.xpPerWorkoutMinute`, label `"Workout: "+name`, `earnedAt=dateEnd`) for non-retroactive completions; injected `LevelingConfigProviderGateway` + `EarnedExperiencePersisterGateway`; `FinishWorkoutDataOutput` gained `?int $earnedXp`. The `LevelingConfig` singleton was seeded via `Version20260613130000` (resolving the flagged trap) + a direct-persist `LevelingConfigFixtures` for dev (see deviation). Migrations applied dev + test. `composer qa` green (**376 tests / 764 assertions**, cs ✅, stan ✅ — 436 files). Phase 0/1/2 fully `[x]`; 3.1→3.5 done. **Uncommitted** since the Phase-2 commit (3.1→3.5 on disk).
- **Next pending step**: **3.6 — Admin LevelBracket CRUD**. 5 UseCases (`Create/Update/Delete/List/GetDetails`) under `UseCase/Admin/Leveling/LevelBracket/`, the contiguity/overlap/single-open-ended/`fromLevel=1`/positive-marginal-cost validators (error code `LEVEL_BRACKET_VALIDATION_FAILED`, with the named sub-codes), `LevelBracketAdminController` (`/api/admin/level-brackets`, 5 routes), and the `frontend/admin/src/features/levelBrackets/` pages incl. a curve-preview chart. The provider methods it needs (`findAllOrderedAsc`, `findContainingLevel`, `findOneByIdForAdminAction`) + the persister are already in place from 3.2–3.4. **Debt for 3.10 (reduced)**: only `database-schema.html` regen (3 Leveling tables + 3 player cols) remains; both Leveling seeds (brackets 3.3, config singleton 3.5) are migration-handled.
- **Running the suite in-container**: integration tests need MySQL and the host's `127.0.0.1` is unreachable from inside the `php` container, so run `docker compose run --rm -e DATABASE_URL="mysql://app:!ChangeMe!@database:3306/akhilleus?serverVersion=8.4&charset=utf8mb4" php vendor/bin/phpunit` (Flex appends the `_test` suffix). Apply migrations to the **test** DB with the same command + `-e APP_ENV=test ... doctrine:migrations:migrate`. The host CLI PHP lacks `mbstring`, so `vendor/bin/phpunit` only runs in-container. `cs`/`stan` run fine on the host. Frontend checks run via `docker compose exec -T frontend-website sh -c "npm run typecheck|lint|build"`.
- **Tooling at the user's disposal in any new session** (added during the v1 work):
  - `composer dev:up` — boots Docker (database + frontend-admin + frontend-website with healthcheck wait), generates the JWT keypair if missing, applies pending dev-DB migrations, starts `symfony serve -d`. Idempotent. Does not touch fixtures.
  - `composer setup:test-db` — provisions `akhilleus_test` + grants for the `app` MySQL user. Run after a fresh `docker compose down -v` or on a brand-new machine.
  - The README "Setup" + "Daily startup" + "Test DB troubleshooting" callouts cover the recovery flows.
- **Uncommitted work at session end**: see `git status`; everything that was implemented in 2.1–2.3 is on disk but not yet committed. Suggested split for the next `git commit` is to bundle the phase-1 + tooling work first, then the phase-2.1→2.3 work as a second commit (the dev-plan ticks lump them all together so order doesn't matter much).
- **v0 leftover non-blockers** still out of v1 scope: Phase 7 manual Chrome/Firefox smoke; Phase 8 prod Dockerfile + `compose.prod.yaml` (deferred until the hosting target is chosen).
- The "Decisions / deviations" block now holds 7 entries (Phase 0 setup gap workaround, Phase 1.1 migration absorbed into mapping step, Phase 1.1 validator error-code convention, Phase 2.1 derived-property initialisation pattern, Phase 3.3 bracket seed via data migration, Phase 3.4 LevelingConfig persister update-only, Phase 3.5 singleton seed migration + direct-persist fixture). Read it before designing anything new.
- `specifications/v1/initial-requirements.md` is the **frozen v1 user spec** — do not edit it. Clarifications/decisions go into this dev-plan.

## Context

`specifications/v1/initial-requirements.md` extends the v0 app with three new player sub-domains — `Tracking`, `Leveling`, `Questing` — plus changes to existing entities (`Movement.videoLink`/`gifLink`, `Workout.status=deleted` + soft-delete + retro/edit propagation rules) and a placeholder `Statistiques` page. `specifications/conventions.md` still applies as-is (final classes, `declare(strict_types=1)`, Yoda conditions, class suffixes, Domain isolation, DTO categories, Repository/Persister + Gateway pattern, UseCase contract, validator typing, DataOutput date formatting). Match the on-disk gateway layout: `Domain/Gateway/{Provider,Persister}/{SubDomain}/{Entity}/{Entity}{Provider|Persister}Gateway.php`.

This plan delivers v1 incrementally. Each numbered subsection is a "step" per `CLAUDE.md`: implement, run `composer qa` (or the relevant subset), tick the box, pause, summarize, wait for "next". Don't chain steps.

The phases are ordered to respect data dependencies:

1. **Foundation** — pre-flight + sub-domain folder scaffolding.
2. **Movement evolutions** (videoLink/gifLink) — small standalone change, picked first to warm up.
3. **Tracking sub-domain** — Steps / Hydration / Sleep / Weight. Must land before Questing (automatic quest metrics read tracking entries).
4. **Leveling sub-domain** (entities + admin curve + XP-on-completion + journal + header). Must land before Questing (claiming a quest reward creates an `EarnedExperience`).
5. **Questing sub-domain** — Quest / QuestProgression + widget + unique-quests page + admin Quest CRUD.
6. **Cron Leveling + workout-side impacts** — locking, soft-delete, edit propagation, retro rules, nightly cron. Grouped here because every constraint flows from the locking story.
7. **Statistics placeholder** — menu + empty page only (chart content deferred per requirements §Improving / Player).
8. **Hardening** — coverage check, CI verification, doc updates.

## Decisions baked in (v1 specifics — v0 carry-overs apply too)

- **Three new parallel sub-domains** (`Tracking`, `Leveling`, `Questing`) sit alongside `Training`. Folder layout mirrors `Training/`: `Domain/DTO/DataModel/{SubDomain}/{Entity}/`, `Domain/Gateway/{Provider,Persister}/{SubDomain}/{Entity}/`, `Domain/Registry/{SubDomain}/{Entity}/`, `Domain/Validator/{Player|Admin}/{SubDomain}/{Entity}/`, `Infrastructure/{Repository,Persister,Controller/{Player|Admin}}/{SubDomain}/{Entity}/`, `UseCase/{Player|Admin}/{SubDomain}/{Entity}/`.
- **Date typing on DataOutputs** — `?string` ISO 8601 (`\DateTimeInterface::ATOM`), formatted at the DTO boundary in the use case. Dates on `DataInput` may be typed `\DateTimeImmutable` (controllers parse and surface parse failures as 422). Same as v0.
- **Player baseline at registration** — `RegisterPlayerUseCase` / `PlayerPersister::create` sets `level=1`, `currentXp=0`, `xpToNextLevel = LevelingCalculator::costFor(2)`, `dailyHydrationTargetMl=1000`. Existing players are backfilled by Phase 3.10's migration with the same values.
- **Aggregate evaluators** — `HydrationAggregateEvaluator` (sum `HydrationEntry.valueMl` into `HydrationDailySummary.amountConsumedMl`), `SleepDurationEvaluator` (`durationMinutes` from `bedAt`/`wakeAt`), and weight-date derivation (`WeightEntry.date` from `loggedAt` in the persister) — all live in `Domain/Service/{SubDomain}/`, mirror the existing `WorkoutAggregateEvaluator` pattern, and run from the matching persister's `update` / `create` paths.
- **Lazy materialization** — `HydrationDailySummary`, `QuestProgression` (daily/weekly/monthly), and `unique` `QuestProgression` rows are auto-created on first read of the consumer endpoint (or first write that needs them). No proactive cron.
- **`xpPerWorkoutMinute` storage** — held on a singleton `LevelingConfigDataModel` (one row, primary-key constant) rather than env / parameters. Reason: admin-editable per requirements; needs the persister + validator pipeline.
- **Ordering invariant on `LevelBracket`** — the validator enforces contiguity (`bracket[i+1].fromLevel = bracket[i].toLevel + 1`), exactly one open-ended bracket (`toLevel = null`, last position), and `fromLevel = 1` on the first bracket. The full curve is reloaded by `LevelingCalculator` on demand.
- **Auto-progression hooks** — `Tracking` write-path UseCases (e.g. `AddHydrationEntryUseCase`, `LogWeightUseCase`, etc.) and `FinishWorkoutUseCase` end by delegating to a `QuestProgressionEvaluator::refreshFor($player, $metric)` that finds-or-creates today's `QuestProgression` for every active `automatic` `Quest` matching the metric and recomputes its `currentValue` + status. Workout COMPLETED also refreshes `WORKOUT_COUNT` / `WORKOUT_DURATION_MINUTES`.
- **Workout `deleted` status** — additive enum value on `WorkoutStatusRegistry`. Every workout read gateway gains a default filter `status != deleted` (callers that want to include deleted rows pass an explicit flag — none in v1).
- **Cron timezone** — Europe/Paris. Day boundary = `00:00:00` Europe/Paris. The nightly cron locks `EarnedExperience` entries with `earnedAt < today 00:00 Europe/Paris`.
- **Schema HTML regeneration** — performed in the same commit as every Doctrine migration created during v1 (per the answer to clarification Q14).

## Open assumptions (flag if you disagree)

- **`xpPerWorkoutMinute` lives on a `LevelingConfigDataModel` singleton.** Alternative: a key-value `AppSetting` entity reusable for future settings. Picked the typed singleton because it gives PHPStan-friendly access and a tight admin-edit path.
- **`Player.dailyHydrationTargetMl` is editable by the player from a profile / settings page** (not admin-only). Plan adds a `UpdatePlayerDailyHydrationTargetUseCase` under `UseCase/Player/Tracking/Hydration/`. If the user prefers admin-only, drop it and let only the daily-summary override be editable.
- **The Tracking dashboard widget shows all 4 trackers in a single composite component** (one widget per requirements wording "A widget will be added"). If preferred, split into 4 widgets.
- **Status invariant on `QuestProgression`** is enforced at the validator level (claim is allowed only when `status === claimable`). The status field is denormalised (could be derived from the date columns), but kept stored for cheap query/index.
- **Workout retro-creation guard** lives in `StartEmptyWorkoutValidator` / `FinishWorkoutValidator` (not at the `WorkoutPersister` level): when `dateEnd < startOfToday(Europe/Paris)`, skip `EarnedExperience` generation but persist the workout normally.
- **No backfill of `EarnedExperience` for pre-v1 completed workouts** — confirmed by clarification Q12. Existing players start v1 at `level=1, currentXp=0`.

## Decisions / deviations from `conventions.md` and the original plan

### Process / step boundaries
- **Phase 1.1 absorbed the migration step originally scheduled in 1.4.** The reason: 1.1 mutates `MovementDataModel`'s ORM mapping, which makes the integration suite go red the moment Doctrine tries to write the now-non-existing columns. Keeping `composer qa` green between every step (the working-mode contract) requires the migration to land in the same step as the mapping change. So `migrations/Version20260505142834.php` was generated + applied on dev + test in 1.1; step 1.4 is reduced to the `database-schema.html` regeneration only. Future schema-touching steps in v1 should follow the same pattern (migration alongside the mapping change, not deferred to a "verification" sub-step).

### Movement validators (1.1)
- **No per-field error code constants on the Movement validators** despite the dev-plan saying `INVALID_VIDEO_LINK_CODE` / `INVALID_GIF_LINK_CODE`. Reason: the v0 convention is one umbrella `ERROR_CODE` per validator (`CREATE_MOVEMENT_VALIDATION_FAILED` / `UPDATE_MOVEMENT_VALIDATION_FAILED`), with violations accumulated under field-named keys in the `violations` map. Per-field codes only happen when a validator throws **distinct** error codes for **distinct** rule families (e.g. `ILLEGAL_STATUS_CODE` vs `TRACKING_MISMATCH_ERROR_CODE` on `AddExerciseSetValidator`). URL format checks fit the existing umbrella code — no new constants needed. The original dev-plan wording was a slip; this is the authoritative reading going forward.

### Steps UseCases (2.4 sub-batch — first one)
- **No `Validator` for `DeleteStepsForDayUseCase`.** Reason: deletion is keyed by `(player, date)` and the provider call `findOneByPlayerAndDate($player, $date)` already scopes to the logged player, so `assertPlayerOwns` is a no-op and there are no other rules. Following the principle "don't create empty abstractions just for symmetry," the use case talks to the gateway directly and throws `EntityNotFoundException` when the lookup misses. The other two Steps UseCases keep their validators (`UpsertStepsForDayValidator` enforces `count >= 0`; `ListStepsForRangeValidator` enforces `from <= to`).
- **`UpsertStepsForDayUseCase` chosen over a separate Create + Update pair.** The dev-plan listed it that way — confirmed during implementation: a daily steps count is naturally idempotent per `(player, date)` and the unique constraint would force any "create" caller to handle the duplicate-key path anyway. One UC, one route, one widget action.
- **Validator-level rules kept minimal**: no future-date guard on `UpsertStepsForDayDataInput.date` (players legitimately backfill yesterday's count), no upper bound on `count` (a marathon day can crest 50k+ steps; arbitrary caps are wrong here). If we later need a "no future dates" guard, apply it consistently across all 4 tracking metrics in one pass.
- **`ListStepsForRangeValidator` is standalone** (does not extend `AbstractLoggedPlayerValidator`) — same shape as `ListWorkoutsByMonthValidator`. List endpoints do not need the logged-player accessor; the use case scopes to the player through the gateway call.

### Hydration UseCases (2.4 sub-batch — second one)
- **Write UseCases return the full day view (`HydrationDayDataOutput`), not the affected entity** (user-approved). `AddHydrationEntry`, `UpdateHydrationEntry`, `DeleteHydrationEntry` and `UpdateHydrationDailyTarget` all return `{date, targetMl, amountConsumedMl, entries[]}` so the dashboard widget refreshes its progress bar + entry list in one round-trip. Small deviation from the Steps pattern (which returned the single entity). `UpdatePlayerDailyHydrationTarget` is the exception — it returns `PlayerHydrationTargetDataOutput {dailyHydrationTargetMl}` because it edits the player-global default, not a day.
- **`AddHydrationEntry` accepts a client-provided `loggedAt`** → the day is derived as `loggedAt->setTime(0,0,0)`, so backfilling a past day is allowed (consistent with the Steps backfill stance). Input = `{loggedAt, valueMl}`.
- **"Today" = `clock->now()->setTime(0,0,0)`** in `GetTodayHydration` / `UpdateHydrationDailyTarget`, with no Europe/Paris timezone juggling at the UseCase level — matches the existing Workout UseCases (`StartEmptyWorkout`, `FinishWorkout`). The Europe/Paris day boundary stays reserved for the Phase 5/6 leveling cron.
- **Lazy-create is persisted**: `GetTodayHydration` and `UpdateHydrationDailyTarget` create + persist today's `HydrationDailySummary` when missing (write-on-read), per the baked-in "lazy materialization" decision. `GetTodayHydration` snapshots `Player.dailyHydrationTargetMl`; `UpdateHydrationDailyTarget` snapshots the requested `targetMl` directly.
- **No validator on `GetTodayHydration` (empty input) nor `DeleteHydrationEntry`** — ownership on entry update/delete is enforced by `findOneByIdForPlayerAction(id, player)` returning `null` → `EntityNotFoundException` (no `assertPlayerOwns`), same gateway-scoped 404 as the Steps delete. The 4 write validators (`Update…DailyTarget`, `UpdatePlayer…Target`, `AddEntry`, `UpdateEntry`) extend `AbstractLoggedPlayerValidator` (mirroring `UpsertStepsForDayValidator`) and enforce the single rule `targetMl > 0` / `valueMl > 0`.
- **Output mapping is built from the in-memory summary, not a re-fetch.** `HydrationEntryPersister` already syncs the `entries` collection and recomputes `amountConsumedMl` in place on create/update/delete, so the UseCases build `HydrationDayDataOutput` from the live summary instance — avoids a nullable re-fetch (which would have tripped PHPStan) and an extra query.

### Sleep UseCases (2.4 sub-batch — third one)
- **`LogSleep` / `UpdateSleep` validators take `validate(PlayerDataModel $player, …)`** (two-arg shape), like `StartEmptyWorkoutValidator` — they inject `SleepDailyEntryProviderGateway` and the UseCase passes the resolved player so the validator can run the `(player, date)` uniqueness check. This differs from the Steps/Hydration validators (single-arg `validate($input)`) which had no cross-row rule. `ListSleepForRangeValidator` stays standalone (no `AbstractLoggedPlayerValidator`), same as `ListStepsForRange`.
- **No validator on `DeleteSleep`** — ownership via `findOneByIdForPlayerAction` → `EntityNotFoundException` (gateway-scoped 404), same as the Steps/Hydration deletes. `UpdateSleep` also relies on the gateway 404 for ownership (no `assertPlayerOwns`); its validator only covers `wakeAt > bedAt`, `quality ∈ [1,5]`, and the duplicate-night guard.
- **`UpdateSleep` duplicate guard excludes the entry itself**: the validator queries `findOneByPlayerAndDate(player, newDate)` and only flags a violation when the found row's id differs from `input->id` — so keeping a night on its own date, or editing its times without moving the date, never trips the `(player, date)` unique constraint.
- **Return shape = the single `SleepDailyEntryDataOutput`** (no "day view" wrapper) since sleep is one record per night — mirrors the Steps entity-return pattern, unlike Hydration's day view (which aggregated multiple entries). `DeleteSleep` returns `DeleteSleepDataOutput {deletedId}` (delete is keyed by id, not by date as in Steps).
- **`date` = `wakeAt->setTime(0,0,0)`** (the wake-up day), computed in the UseCase and re-applied on update; `durationMinutes` stays auto-derived by `SleepDurationEvaluator` from the persister. No future-date guard on `wakeAt` (consistent with the Steps "no arbitrary date caps" stance).

### Weight UseCases (2.4 sub-batch — fourth, closes 2.4)
- **Structurally a clone of Sleep** (one entry per day, `(player, date)` unique, gateway-scoped 404 on update/delete, two-arg `validate(player, input)` on `Log`/`Update` injecting `WeightEntryProviderGateway`, standalone `ListWeightForRangeValidator`, single-entity returns + `DeleteWeightDataOutput {deletedId}`). Same deviations as the Sleep block apply.
- **Validator rule = `valueGrams > 0` + the `(player, date)` uniqueness guard** (Update excludes self by id). The dev-plan only spelled out the duplicate rule; the positive-value guard was added for parity with the other metrics (a zero/negative weight is meaningless) — no arbitrary upper bound.
- **`ListWeightForRange` widens the closing bound to end-of-day.** Unlike Steps/Sleep (whose range gateways filter on the `date` column), `WeightEntryRepository::findAllByPlayerForRange` filters on `loggedAt` (a datetime, to feed the progression chart in time order). So the UseCase passes `from->setTime(0,0,0)` and `to->setTime(23,59,59,999999)` to keep the range inclusive of an entry logged at any time on the closing date (covered by a dedicated test). `date` stays auto-derived from `loggedAt` by `WeightEntryPersister` on create/update — the UseCase doesn't set it.

### Steps daily target (post-2.6 addition — mirrors the hydration target)
Added on user request (2026-06-13): a daily step **goal**, replicating the hydration target mechanism end-to-end. Spans 2.4/2.5/2.6 retroactively (those boxes stay `[x]`; this is an additive extension, not a reopen).
- **Two levels, like hydration**: `Player.dailyStepsTarget` (global default **5000**, editable) + `StepsDailyEntry.target` (snapshotted from the player default at create time, editable per day). Migration `Version20260613111300` (both columns `INT DEFAULT 5000 NOT NULL`, existing rows backfilled).
- **`StepsDailyEntryDataModel` constructor gained a required `int $target`** (4th arg) — same shape as `HydrationDailySummary` taking `targetMl`. The two `UpsertStepsForDay` create-path callers + the new UCs snapshot `player->dailyStepsTarget`; `StepsDailyEntryDataOutput` gained `target`.
- **New UCs** under `UseCase/Player/Tracking/Steps/`: `GetTodayStepsUseCase` (lazy-creates today's entry with `count=0`, `target=player default` — mirrors `GetTodayHydration`, a new read-creates-row case for steps), `UpdateStepsDailyTargetUseCase` (today's per-day target, lazy-create), `UpdatePlayerDailyStepsTargetUseCase` (global default). Two new validators (`target > 0`).
- **Controller**: `StepsPlayerController` gained `GET /steps/today`, `PUT /steps/today/target`, `PUT /steps/target`. To stop the static `today`/`target` segments being swallowed by `PUT/DELETE /steps/{date}`, the `{date}` routes now carry a `requirements: ['date' => '\d{4}-\d{2}-\d{2}']` ISO-date constraint.
- **Frontend**: `StepsCard` rewritten to mirror `HydrationCard` (progress bar `count / target`, inline-editable day target ✎); it now reads via `getTodaySteps` (server-side "today", lazy-created) instead of the `[today,today]` range, and derives the upsert date from the response (`date.slice(0,10)`) so the count write targets the same day the server resolved. `api/types.ts`, `api/endpoints/tracking.ts`, `hooks/tracking/` extended accordingly.
- **`database-schema.html`**: not updated here — folded into the still-pending 2.7 wholesale regen (the doc is missing all Phase-2 tables; see 2.7).

### Tracking DataModels (2.1)
- **`WeightEntryDataModel.date` is derived in the constructor**, not exclusively in the persister, breaking the v0 "derived properties live on the model and are computed in the persister" rule. Reason: `date` is a non-nullable `DATE_IMMUTABLE` column used in the unique constraint `(player_id, date)`. PHP requires it to be initialised before persist, and `\DateTimeImmutable` has no `''`-equivalent default value (unlike `string $slug = ''` on `MovementDataModel`, which the persister overwrites). Cleanest accommodation: `$this->date = $loggedAt->setTime(0, 0, 0)` in the constructor — one-line, no logic, mirrors the persister's behaviour. The persister (Phase 2.3) will still recompute on update so `loggedAt` mutations stay in sync.
- **`SleepDailyEntryDataModel.durationMinutes` and `HydrationDailySummaryDataModel.amountConsumedMl` keep the slug-style "default placeholder, persister overwrites" pattern** (defaults `0`). Their derivation involves real logic (`floor((wake − bed) / 60)`, sum of entry values) that belongs in a domain service (`SleepDurationEvaluator`, `HydrationAggregateEvaluator`) called from the persister — duplicating it in the constructor would split the source of truth.

### Leveling bracket seed via data migration (3.3 — resolves the registration baseline dependency)
- **The 3 baseline `LevelBracket` rows are seeded by a data migration (`Version20260613120000`), pulled forward from the 3.10 seed step**, instead of by fixtures only. `PlayerPersister::create` computes `xpToNextLevel = LevelingCalculator::marginalCostFor(2)` at registration, which reloads the curve from the DB — so *every* environment that creates a Player needs `LevelBracket` rows present. The decisive constraint is the **test** DB: DAMA DoctrineTestBundle wraps each integration test in a transaction and rolls it back on top of the *committed* state, and the test DB is built by `doctrine:migrations:migrate` (CI + local) — it does **not** load fixtures, and tests create their own reference data inline. A fixtures-only seed therefore leaves all 36 player-creating integration tests hard-failing with `No level bracket covers level 2`. Seeding via migration puts the curve in the committed state every environment shares, fixing all 36 tests with **zero test edits** and keeping registration strict (no silent empty-curve fallback). The rejected alternative (option b) was a defensive `try/catch` in `PlayerPersister` falling back to the column default 4000 when the curve is empty — rejected because it masks a genuinely mis-seeded environment.
- **Asymmetry accepted**: brackets now live in *both* a migration (committed baseline for prod + test DB) *and* `LevelBracketFixtures` (so `doctrine:fixtures:load`, which purges all tables, re-creates them in dev). Other reference data (muscles, equipment) lives in fixtures only because tests build it inline; brackets can't be built inline since the dependency is implicit in `PlayerPersister`. The fixed ULIDs in the migration (`01JBRACKET0000000000000001..3`) keep the seed deterministic and re-runnable.
- **Pulled forward from 3.4**: `LevelBracketPersisterGateway` + `LevelBracketPersister` (needed by the fixture). 3.4 now only owes the two extra `LevelBracket` provider methods. **3.10's seed debt is reduced** to just the `LevelingConfig` singleton (the brackets are done).

### LevelingConfig persister exposes only `update` (3.4)
- **`LevelingConfigPersisterGateway` declares only `update`, not the usual create/update/delete triplet.** Two reasons: (1) the singleton carries a **fixed id** (`LevelingConfigDataModel::LEVELING_CONFIG_ID`), but `AbstractBaseMysqlPersister::doCreate` unconditionally overwrites `$model->id` with a fresh ULID — so a create path through the base persister would silently break the well-known id; (2) a singleton is never deleted. The row is seeded by migration (Phase 3.10, mirroring the 3.3 bracket-seed decision) and edited by the admin via `update` (Phase 3.7). If 3.10 ever opts to seed via a persister instead of a migration, it must add a *singleton-aware* `create` that bypasses the ULID generation — do not route it through `doCreate`.
- **`getSingleton()` throws `\LogicException` when the row is missing** (not `EntityNotFoundException`), consistent with `LevelingCalculator`'s "no bracket covers level" throw: a missing singleton is a deployment/seed misconfiguration (500-class), not a client-facing 404. **Dependency flag for 3.5**: `FinishWorkoutUseCase` will call `getSingleton()` to read `xpPerWorkoutMinute` — so the singleton must be seeded before 3.5's integration tests pass, exactly the same trap as the brackets in 3.3. Seed it via migration at the start of 3.5 (or fold into 3.5's work) rather than waiting for 3.10. **[Resolved in 3.5 — see below.]**

### LevelingConfig singleton seed via migration + direct-persist fixture (3.5)
- **The `LevelingConfig` singleton is seeded by `Version20260613130000`** (fixed id `01000000000000000000000000`, `xp_per_workout_minute=50`), same rationale as the 3.3 bracket seed: it puts the row in the committed state shared by dev, the migrated test DB, and prod, so `FinishWorkoutUseCase::getSingleton()` resolves everywhere and the workout-finish tests pass with no per-test seeding. Migrations applied dev + test.
- **`LevelingConfigFixtures` persists the singleton *directly through the `ObjectManager`*, not via a `PersisterGateway`** — the one fixture in the codebase that breaks the "fixtures inject the matching `*PersisterGateway` and call `create(...)`" rule. Reason: the singleton carries a fixed well-known id, but `AbstractBaseMysqlPersister::doCreate` overwrites `id` with a fresh ULID, and the gateway is deliberately `update`-only (deviation #6). The fixture sets `id` (constructor default) + `createdAt`/`updatedAt` itself and calls `$manager->persist()/flush()`. It exists so `doctrine:fixtures:load` (which purges every table) re-seeds the singleton in dev — the migrated baseline would otherwise vanish locally. `LevelBracketFixtures` (3.3) keeps using its persister because brackets have generated ids.
- **Retro guard is in the use case, not the validator (yet).** The "Open assumptions" block proposes the retro guard living in `FinishWorkoutValidator`; for 3.5 it sits inline in `FinishWorkoutUseCase::awardWorkoutExperience` (`dateEnd < startOfToday(Europe/Paris)` → no XP). Since `FinishWorkout` always sets `dateEnd = now`, the guard is currently a no-op that documents the rule; Phase 5.6 (retro creation / same-day edit) will decide whether to hoist it into the validator. `duration` on the workout is stored in **seconds** (`WorkoutAggregateEvaluator`), so the use case recomputes minutes from the `dateStart`/`dateEnd` timestamps directly rather than reusing `$workout->duration`.

### Admin LevelingConfig (3.7)
- **`GetLevelingConfigUseCase` extends `AbstractPublicUseCase`, not `AbstractLoggedAdminUseCase`** — read-only admin get with no auth-resolution need, matching the existing `GetLevelBracketDetailsUseCase` precedent. No validator on Get (empty `GetLevelingConfigDataInput`). The route is still gated by `ROLE_PLAYER`-equivalent admin firewall config; the use-case base only governs validator injection.
- **`UpdateLevelingConfigValidator` takes no provider gateway** — the only rule is `xpPerWorkoutMinute ≥ 50` (a single-field bound, no cross-row check), so the constructor only wires `LoggedUserResolverInterface` to the parent. Integer-ness is enforced by the PHP-typed `UpdateLevelingConfigDataInput`.
- **Frontend uses an inline antd `Card` + `Form`, not `EntityFormShell`** — a singleton edit-in-place has no create/cancel/navigate flow, so `EntityFormShell` (which renders a Cancel-to-route button and is built for full CRUD pages) doesn't fit. The card replicates `EntityFormShell`'s 422→field-error mapping inline (`form.setFields` from `ApiError.violations.xpPerWorkoutMinute`) since `applyViolationsToForm` is not exported. Co-located everything (types+api+hooks) in one `levelingConfig.ts` rather than the usual `types.ts`/`api.ts`/`hooks.ts` triple, because the singleton has only 2 endpoints and no separate detail/list/create surface — splitting would have produced three near-empty files.

### Player XP journal (3.8)
- **Dev-plan wording vs on-disk reality:** the step said the journal page lives at `frontend/website/src/features/leveling/JournalPage.tsx` and is "linked from the header dropdown". Neither matches the website: there is **no `features/` dir** (pages live in `pages/`, per the 2.6 deviation) and the header (`components/layout/NavBar.tsx`) is a **flat `LINKS` array of `<NavLink>`s, not a dropdown**. Implemented as `pages/leveling/XpJournalPage.tsx` with a flat NavBar entry "Journal XP". Future header-mounted leveling UI (3.9 progress bar) should target `NavBar`/`AppLayout`, not a dropdown.
- **No website icon set exists** (the `components/icons/index.tsx` kit described in `CLAUDE.md` is admin-side / not present here). The lock indicator is a single inline `<svg stroke="currentColor">` defined in the page — theme-agnostic, no new dependency. If icon reuse grows, extract a website icon module then.
- **`EarnedExperienceDataOutput.earnedAt` typed `?string`** even though the DB column is non-null — keeps the "DataOutput dates are nullable ISO strings formatted at the UC boundary" convention uniform across the codebase.
- **No `assertPlayerOwns` / no `AbstractLoggedPlayerValidator`** for the journal: it's a list scoped to the logged player through the gateway call (`findAllByPlayerForJournal($player, …)`), so `ListEarnedExperienceValidator` is standalone (page/perPage bounds only), mirroring `ListWorkoutHistoryValidator`.

### Player profile endpoint + header badge (3.9)
- **New `GET /api/player/profile` endpoint added** (the dev-plan said "or the equivalent already returned by an existing endpoint — verify on disk"; none existed). `GetPlayerProfileUseCase` lives under `UseCase/Player/Profile/` with an empty `GetPlayerProfileDataInput` and **no validator** (read scoped to the logged player via `LoggedPlayerResolverInterface`, same shape as `GetTodayHydrationUseCase`). `PlayerProfileDataOutput` carries `{id, displayName, level, currentXp, xpToNextLevel}` — `displayName` included so the header has a single source for player identity (the website previously had none; `AuthContext` only stores the JWT). Controller at `Infrastructure/Controller/Player/Profile/`.
- **`<PlayerLevelBadge />` placed in `frontend/website/src/components/layout/`** (the dev-plan said `src/layout/`, which doesn't exist; layout components live under `components/layout/` next to `NavBar`/`AppLayout`). Mounted inside `NavBar`, grouped with the brand on the left (no separate `AppLayout` header / dropdown exists). Renders `null` until `useProfile()` resolves to avoid layout jank.
- **Profile data is its own TanStack query** (`hooks/profile/`, key `['profile','me']`), not folded into `AuthContext`. Future writes that change level/XP (the Phase 5/6 cron is server-side, but any client-side XP change) must `invalidateQueries(['profile'])` to refresh the badge.

## Tracking (checkbox convention)

- The dev-plan uses `[x]` / `[ ]` checkboxes on every subsection header **and** every leaf bullet. Tick items off as soon as the step closes. This is the source of truth for "what's done."
- `[~]` marks a subsection where some leaf bullets are still `[ ]` (partial — keep until every leaf is `[x]`).

---

## Phase 0 — Foundation & v1 alignment

### [x] 0.1 Pre-flight on the inherited codebase
- [x] On a fresh `composer install` + `npm ci` (both frontends), confirm `composer qa` green: cs ✅, stan ✅, phpunit ✅ (241 tests / 507 assertions, exactly the v0 close-out baseline).
- [x] `php bin/console doctrine:schema:validate` returns "in sync" against the dev DB. (Required first migrating the dev DB — both `akhilleus` and `akhilleus_test` were empty on this machine; see notes below.)
- [x] `npm run typecheck && lint && build` green on `frontend/admin` and `frontend/website`. The pre-existing chunk-size warning on `frontend-admin` is unchanged from v0.
- [x] Drift noted: **PHP 8.5.5 on the host vs PHP 8.4 in CI** (per v0 dev-plan's `backend` job). `composer.json` requires `>=8.4` so both work, but care must be taken not to use 8.5-only features in v1 code (CI would catch it on push).

**Setup gap fixed during 0.1 (not a deviation, but worth recording):**
- The test DB `akhilleus_test` was missing and the `app` MySQL user had no privileges on `akhilleus_test%`, blocking the integration suite with `SQLSTATE[HY000] [1044] Access denied`. Same root cause for the empty dev DB. Added a re-runnable, idempotent `composer setup:test-db` script (`docker/mysql/setup-test-db.sql` + entry in `composer.json`) and documented the recovery path in `README.md` (new step 4 in the setup walkthrough + a "Test DB troubleshooting" callout). The dev DB simply needed `php bin/console doctrine:migrations:migrate --no-interaction` + `doctrine:fixtures:load`.

### [x] 0.2 Sub-domain scaffolding stubs
Create empty folders so subsequent phases just drop files in. Nothing is committed until the entity it hosts lands (Phase 1+) — Git does not track empty directories, so `git status` stays clean after the `mkdir`.
- [x] `src/Domain/DTO/DataModel/{Tracking,Leveling,Questing}/`
- [x] `src/Domain/Gateway/{Provider,Persister}/{Tracking,Leveling,Questing}/`
- [x] `src/Domain/Registry/{Leveling,Questing}/` (Tracking has no registry — values are not enums).
- [x] `src/Domain/Service/{Tracking,Leveling,Questing}/` for the aggregate / calculator services.
- [x] `src/Domain/Validator/{Player,Admin}/{Tracking,Leveling,Questing}/`
- [x] `src/Infrastructure/{Repository,Persister}/{Tracking,Leveling,Questing}/`
- [x] `src/Infrastructure/Controller/{Player,Admin}/{Tracking,Leveling,Questing}/`
- [x] `src/UseCase/{Player,Admin}/{Tracking,Leveling,Questing}/`
- [x] `src/Domain/DTO/{DataInput,DataOutput}/{Player,Admin}/{Tracking,Leveling,Questing}/`

50 directories created. The cartesian product `{Player,Admin} × {Tracking,Leveling,Questing}` results in a few combinations that v1 does not actually populate (e.g. `Validator/Admin/Tracking/`, `DTO/DataInput/Admin/Tracking/` — there is no admin path on tracking metrics in v1). They stay empty and harmless; the actual phases populate only the cells they need.

### [x] 0.3 Verification
- [x] `composer qa` green (no new tests yet — empty folders are inert). 241 tests / 507 assertions.
- [x] `php bin/console debug:container --env=dev` runs without complaints — service catalogue lists cleanly, no misconfiguration warnings.

---

## Phase 1 — Movement evolutions (`videoLink` + `gifLink`)

### [x] 1.1 DataModel + admin DTOs
- [x] Add nullable `?string $videoLink` and `?string $gifLink` columns to `MovementDataModel` (`Type::STRING`, length 2048, nullable). Both default to `null`.
- [x] Update `Domain/DTO/DataInput/Admin/Training/Movement/CreateMovementDataInput` and `UpdateMovementDataInput` to accept the two optional URL fields.
- [x] Update `Domain/DTO/DataOutput/Admin/Training/Movement/MovementDataOutput`. `MovementListItemDataOutput` left alone — the list stays slim (no URLs in the table view).
- [x] Update `CreateMovementValidator` and `UpdateMovementValidator`: when non-null, both fields must pass `filter_var(..., FILTER_VALIDATE_URL)`. **No new error code constants** — see deviation below; violations accumulate under the existing umbrella `CREATE_MOVEMENT_VALIDATION_FAILED` / `UPDATE_MOVEMENT_VALIDATION_FAILED` codes, with the field name as the violations-map key.
- [x] **Implicit add (not in the original step bullets)**: wired the new fields end-to-end through the use cases. `CreateMovementUseCase`, `UpdateMovementUseCase` and `GetMovementDetailsUseCase` all assign `$movement->videoLink` / `$movement->gifLink` and pass them when constructing `MovementDataOutput`. Without this, the persisted/loaded fields would never reach the JSON response.
- [x] **Migration generated + applied here, not in 1.4**: see deviation below. `migrations/Version20260505142834.php` (`ALTER TABLE movement ADD video_link, gif_link VARCHAR(2048) NULL`) applied on both dev and test DBs.

### [x] 1.2 Admin path: REST + frontend
- [x] `MovementAdminController::create` and `::update` **did need a change** despite the dev-plan note: the controller builds the `DataInput` constructor by hand (positional args), so the two new fields had to be passed explicitly. Added a `nullableString` helper next to `stringList` that converts missing/empty payload values to `null` (so an empty form input lands as `null` rather than `''` and the `FILTER_VALIDATE_URL` rule doesn't trip on empty strings). Dev-plan was slightly over-optimistic on "no controller change".
- [x] `frontend/admin/src/features/movements/MovementForm.tsx`: added two `<Input type="url">` `Form.Item`s (`videoLink`, `gifLink`) with placeholders. AntD's `Form` already maps backend `violations[fieldName]` into per-field errors via `EntityFormShell`, so no extra plumbing.
- [x] `MovementEditPage`: passes `movement.videoLink` / `movement.gifLink` into `initialValues` so editing a movement renders the existing URLs.
- [x] `MovementListPage`: untouched — already excludes URLs from the table (only `Label` + `Main muscle` + actions).
- [x] `types.ts`: extended `Movement` and `MovementFormValues` with `videoLink: string | null` + `gifLink: string | null`.

### [x] 1.3 Player workout view: render the URLs
- [x] Surfaced `videoLink` + `gifLink` on `Domain/DTO/DataOutput/Player/Training/Exercise/ExerciseMovementDataOutput` (note: actual on-disk path is `.../Exercise/...`, not `.../Workout/...` as the dev-plan stated). 4 construction sites updated: `GetWorkoutDetailsUseCase`, `AddMovementToWorkoutUseCase`, `UpdateMovementRestDurationUseCase`, `ReorderMovementsUseCase`.
- [x] `frontend/website/src/api/types.ts`: extended `ExerciseMovementDataOutput` with `videoLink: string | null` + `gifLink: string | null`.
- [x] **New shared component** `frontend/website/src/components/workout/MovementMediaLinks.tsx` — renders the "▶ Voir la démo" link (when `videoLink` non-null) + a small GIF thumbnail (max 60×100 px, click opens full size in a new tab via `<a target="_blank" rel="noopener noreferrer">`). Returns `null` when both are null. Theme-agnostic (CSS variables).
- [x] Mounted in `<ExerciseEditor>` under the label / rest line — covers `<PlannedWorkoutView>` and `<LiveWorkoutEditor>` automatically (both compose `<ExerciseEditor>`).
- [x] Mounted in `<ReadOnlyWorkoutView>` next to the label (the read-only path renders its own exercise headers, doesn't go through `<ExerciseEditor>`).

### [x] 1.4 Migration + schema HTML
- [x] `php bin/console make:migration` → review/clean the generated migration (only ALTER TABLE on `movement`, two nullable VARCHAR(2048)). **Done as part of 1.1** — see deviation note. File: `migrations/Version20260505142834.php`.
- [x] Apply on dev + test DBs. **Done as part of 1.1.**
- [x] Regenerate `specifications/database-schema.html` — added `video_link` + `gif_link` rows (both `VARCHAR(2048) NULL`) to the `movement` table block, between `tracks_incline_meters` and `created_at`. Updated the table summary to mention the two URL columns.

### [x] 1.5 Verification
- [x] Validator unit tests: happy path (existing) + new `testItAcceptsValidVideoAndGifUrls`, `testItRejectsInvalidVideoLink`, `testItRejectsInvalidGifLink`, and on `CreateMovementValidatorTest` `testItAccumulatesViolationsAcrossUrlAndOtherFields` (which doubles as accumulation + both-null fallback). Same 3 (+1 accumulation) tests on `UpdateMovementValidatorTest`. Both-null OK is implicit in the existing happy-path tests since the new constructor params default to null.
- [x] Integration tests: `CreateMovementUseCaseTest::testItPersistsVideoAndGifLinks` + `testItRejectsInvalidVideoLinkUrl`, `UpdateMovementUseCaseTest::testItUpdatesVideoAndGifLinks` + `testItRejectsInvalidVideoLinkUrlOnUpdate`. Confirms persistence round-trip and validation paths against the real DB.
- [x] cURL smoke (full HTTPS path against `symfony serve`): admin login → JWT → create movement with both URLs (`https://example.com/demo.mp4`, `https://example.com/demo.gif`) returns 201 with the URLs surfaced → `GET /api/admin/movements/{id}` confirms persistence → `PUT` with `videoLink: "not-a-url"` returns **422** with `errorCode: UPDATE_MOVEMENT_VALIDATION_FAILED` and `violations.videoLink: ["Video link must be a valid URL."]` → `DELETE` returns 204. **Note**: the JWT keypair was missing on this machine (same kind of setup gap as the empty databases earlier); ran `php bin/console lexik:jwt:generate-keypair` once to provision it (gitignored, no commit needed).
- [x] **Frontend manual** (validated by user): admin successfully created/edited movements with both URL fields (saved + invalid-URL inline error verified); player saw `▶ Voir la démo` link + GIF thumbnail in the exercise card and both opened in new tabs as expected.
- [x] `composer qa` green (252 tests / 527 assertions — up from the 241/507 baseline, all 11 new tests pass) ; `npm run typecheck && lint && build` green on both frontends (last verified end of 1.2 / 1.3, no frontend code touched since).

---

## Phase 2 — Tracking sub-domain

Goal: `Steps`, `Hydration`, `Sleep`, `Weight` end-to-end (DataModel → use cases → REST → dashboard widget) with the snapshot/lazy/aggregate semantics from `specifications/v1/initial-requirements.md`.

### [x] 2.1 DataModels + Player change
- [x] Added `int $dailyHydrationTargetMl` (non-null, default 1000) on `PlayerDataModel`. Doctrine `options: ['default' => 1000]` makes the migration backfill existing rows automatically — no separate UPDATE needed.
- [x] `Domain/DTO/DataModel/Tracking/Steps/StepsDailyEntryDataModel` — `player` (M:1), `date` (DATE), `count` (INT). Unique constraint `uniq_steps_daily_entry_player_date (player_id, date)`. Implements `OwnedByPlayerInterface` directly.
- [x] `Domain/DTO/DataModel/Tracking/Hydration/HydrationDailySummaryDataModel` — `player`, `date`, `targetMl` (INT non-null, snapshotted by the persister at create time from `Player.dailyHydrationTargetMl`), `amountConsumedMl` (INT non-null, default 0, recomputed by `HydrationAggregateEvaluator`). Has the OneToMany inverse `entries` to `HydrationEntryDataModel` (orphan removal on). Unique on (`player_id`, `date`).
- [x] `Domain/DTO/DataModel/Tracking/Hydration/HydrationEntryDataModel` — `summary` (M:1, `inversedBy: 'entries'`), `loggedAt` (DATETIME), `valueMl` (INT). Implements `OwnedByPlayerInterface` via virtual property hook `public PlayerDataModel $player { get => $this->summary->player; }` — same pattern as v0's `ExerciseDataModel`/`ExerciseSetDataModel`.
- [x] `Domain/DTO/DataModel/Tracking/Sleep/SleepDailyEntryDataModel` — `player`, `date` (DATE — wake-up date), `bedAt` (DATETIME), `wakeAt` (DATETIME), `durationMinutes` (INT, default 0, will be overwritten by `SleepDurationEvaluator` from the persister), `quality` (nullable SMALLINT, range `[1,5]` enforced at validator level — out of scope for 2.1). Unique on (`player_id`, `date`).
- [x] `Domain/DTO/DataModel/Tracking/Weight/WeightEntryDataModel` — `player`, `loggedAt` (DATETIME), `valueGrams` (INT), `date` (DATE — derived from `loggedAt->setTime(0,0,0)` in the constructor; the persister will recompute on update for consistency). Unique on (`player_id`, `date`). See deviation note below.
- [x] All five new DataModels carry `createdAt` / `updatedAt` per the `AbstractBaseMysqlPersister` contract (set by the parent persister's `doCreate` / `doUpdate`).
- [x] **Migration generated + applied here, not in 2.7** (same pattern as Phase 1.1 — see deviations block). `migrations/Version20260505153556.php`: 5 CREATE TABLE + 5 ADD CONSTRAINT (FKs to `player` / `hydration_daily_summary`) + 1 ALTER TABLE adding `daily_hydration_target_ml INT DEFAULT 1000 NOT NULL` to `player`. Applied on dev + test DBs.
- [x] `composer qa` ✅ (252/527, baseline preserved); `php bin/console doctrine:schema:validate` ✅ (mapping + database in sync). Schema HTML regen deferred to step 2.7.

### [x] 2.2 Aggregate / derivation services
- [x] `Domain/Service/Tracking/Hydration/HydrationAggregateEvaluator::recompute(HydrationDailySummaryDataModel $summary): HydrationDailySummaryDataModel` — sums all `HydrationEntry.valueMl` linked to `$summary` into `$summary->amountConsumedMl`. Mutates in place + returns the same instance (mirrors `WorkoutAggregateEvaluator`). Stateless `final readonly` class with a single `static` method. Will be triggered by `HydrationEntryPersister::create / update / delete` and by `HydrationDailySummaryPersister::update` once Phase 2.3 lands.
- [x] `Domain/Service/Tracking/Sleep/SleepDurationEvaluator::recompute(SleepDailyEntryDataModel $entry): SleepDailyEntryDataModel` — `durationMinutes = floor((wakeAt − bedAt) / 60)` via `getTimestamp()` arithmetic. Caller responsibility to ensure `wakeAt > bedAt` (validator on the use case enforces it in Phase 2.4); the service does not re-check, mirroring how `WorkoutAggregateEvaluator` trusts its caller.
- [x] **No service for Weight date derivation** — confirmed: `$model->date = $model->loggedAt->setTime(0, 0, 0)` is a one-liner that lives in the constructor of `WeightEntryDataModel` (already done in 2.1) and will be re-applied by `WeightEntryPersister::create / update` (Phase 2.3) on every persist for safety.
- [x] **Layout decision (sub-folders)**: new evaluators live under `Domain/Service/Tracking/{Hydration,Sleep}/` (sub-folder per sub-domain + entity), not flat under `Domain/Service/` like the v0 evaluators. Consistent with the 0.2 scaffolding that already created these sub-folders. v0 evaluators stay flat — moving them would be a separate refactor outside v1 scope.
- [x] Unit tests under `tests/Unit/Domain/Service/Tracking/{Hydration,Sleep}/`:
  - `HydrationAggregateEvaluatorTest` (4 tests): sums multiple entries, resets stale value to 0 when collection is empty, single-entry path, idempotent on repeated calls.
  - `SleepDurationEvaluatorTest` (4 tests): exact 8h sleep crossing midnight, sleep within the same day, floor on partial-minute remainder, overwrites stale duration on re-run.
- [x] `composer qa` ✅ — 260 tests / 537 assertions (+8 / +10 vs Phase 2.1 baseline).

### [x] 2.3 Gateways + Repositories + Persisters
20 files landed (5 entities × 4 files each: Provider gateway interface + Persister gateway interface + Repository impl + Persister impl). All under the matching `Tracking/{Steps,Hydration,Sleep,Weight}/` sub-folders. Concrete provider methods chosen per entity based on the use cases planned for Phase 2.4:

- [x] **Steps** (`StepsDailyEntryProviderGateway`):
  - `findOneByPlayerAndDate(player, date)` — for upsert and delete-by-date.
  - `findAllByPlayerForRange(player, from, to)` — inclusive range, ordered by date ASC.
- [x] **HydrationDailySummary** (`HydrationDailySummaryProviderGateway`):
  - `findOneByPlayerAndDateWithEntries(player, date)` — eager-fetches the `entries` collection (LEFT JOIN + addSelect) so the widget renders without a follow-up query. Single query covers both the read path and the lazy-create check.
- [x] **HydrationEntry** (`HydrationEntryProviderGateway`):
  - `findOneByIdForPlayerAction(id, player)` — INNER JOIN `summary`, scoped to `summary.player = :player`. Replaces the manual ownership check at the use case level.
- [x] **SleepDailyEntry** (`SleepDailyEntryProviderGateway`):
  - `findOneByPlayerAndDate(player, date)` — uniqueness check from the create validator.
  - `findOneByIdForPlayerAction(id, player)` — Update / Delete ownership scoping.
  - `findAllByPlayerForRange(player, from, to)` — inclusive, ordered by date ASC.
- [x] **WeightEntry** (`WeightEntryProviderGateway`):
  - Same three methods as Sleep, with `findAllByPlayerForRange` ordered by `loggedAt ASC` (feeds the future progression chart in time-of-day order, not just per-day).
- [x] **Persister gateways** all expose the standard `create / update / delete` triplet typed per `DataModel`.
- [x] **Persisters** (`extends AbstractBaseMysqlPersister<TDataModel>`, `final readonly`):
  - `StepsDailyEntryPersister`, `HydrationDailySummaryPersister`: pass-through to `doCreate / doUpdate / doDelete`.
  - `SleepDailyEntryPersister`: calls `SleepDurationEvaluator::recompute($model)` before `doCreate` and `doUpdate` to keep `durationMinutes` in sync with `bedAt` / `wakeAt`.
  - `WeightEntryPersister`: re-derives `$model->date = $model->loggedAt->setTime(0, 0, 0)` before each `doCreate` / `doUpdate` so the `(player, date)` unique constraint stays in sync if `loggedAt` was mutated.
  - `HydrationEntryPersister`: **injects `HydrationDailySummaryPersisterGateway`**. After every entry create / update / delete, runs `HydrationAggregateEvaluator::recompute` on the parent summary and persists the new `amountConsumedMl` via the summary persister. Manually keeps the `summary->entries` collection in sync (`->add()` after create, `->removeElement()` before delete) — same identity-map trap as v0's `AddMovementToWorkoutUseCase` (Doctrine returns the cached parent on subsequent calls, the inverse collection is never auto-refreshed).
- [x] No new tests in this step — repositories and persisters are exercised via the Phase 2.4 / 2.8 use case integration tests. PHPStan + CS already enforce the structural correctness (`composer qa` ✅, still at 260 / 537).

### [x] 2.4 Player UseCases per metric
All under `UseCase/Player/Tracking/...`, extending `AbstractLoggedPlayerUseCase`. Validators extend `AbstractLoggedPlayerValidator` (or are standalone for List shapes). Player-edit validators implement `assertPlayerOwns` against the `OwnedByPlayerInterface` virtual hook on each tracking DataModel (where applicable: `HydrationEntry::$player` virtual hook → `$this->summary->player`).

- [x] **Steps** (`UseCase/Player/Tracking/Steps/`):
  - [x] `UpsertStepsForDayUseCase` — `(date, count)` → create or update the `StepsDailyEntry` for that day.
  - [x] `DeleteStepsForDayUseCase` — soft-not-needed, hard delete is fine; daily values are user-correctible.
  - [x] `ListStepsForRangeUseCase` — read-only listing (used by widget + future stats).
- [x] **Hydration** (`UseCase/Player/Tracking/Hydration/`):
  - [x] `GetTodayHydrationUseCase` — returns the day's `HydrationDailySummary` + the list of `HydrationEntry`. Lazy-creates the Summary if missing (snapshots `Player.dailyHydrationTargetMl` into `targetMl`).
  - [x] `UpdateHydrationDailyTargetUseCase` — overrides `targetMl` for a specific day's Summary (does **not** touch `Player.dailyHydrationTargetMl`).
  - [x] `UpdatePlayerDailyHydrationTargetUseCase` — updates `Player.dailyHydrationTargetMl` (global default for new days). Editable by the player from a profile section.
  - [x] `AddHydrationEntryUseCase` — creates a `HydrationEntry`; auto-creates the day's Summary if missing; recomputes the Summary aggregate.
  - [x] `UpdateHydrationEntryUseCase` — same recompute on the linked Summary.
  - [x] `DeleteHydrationEntryUseCase` — same recompute.
- [x] **Sleep** (`UseCase/Player/Tracking/Sleep/`):
  - [x] `LogSleepUseCase` — `(bedAt, wakeAt, quality?)`. Computes `date = wakeAt::date`. Validator: `wakeAt > bedAt`, quality ∈ `[1,5]` when non-null, no duplicate for `(player, date)`.
  - [x] `UpdateSleepUseCase` — same rules + ownership.
  - [x] `DeleteSleepUseCase`.
  - [x] `ListSleepForRangeUseCase`.
- [x] **Weight** (`UseCase/Player/Tracking/Weight/`):
  - [x] `LogWeightUseCase` — `(loggedAt, valueGrams)`. Validator rejects duplicate (`player`, `date(loggedAt)`).
  - [x] `UpdateWeightUseCase`.
  - [x] `DeleteWeightUseCase`.
  - [x] `ListWeightForRangeUseCase` (powers the future progression graph).

### [x] 2.5 Player REST controllers
Under `Infrastructure/Controller/Player/Tracking/`. Match the `WorkoutPlayerController` style (`POST` for actions, `GET` for reads, `PUT` for updates, `DELETE` for deletes). All routes under `/api/player/tracking/*`, gated by `ROLE_PLAYER`. 17 routes total, all attribute-based, auto-discovered, confirmed via `debug:router`.
- [x] `StepsPlayerController` — `PUT /api/player/tracking/steps/{date}` (upsert), `DELETE /api/player/tracking/steps/{date}`, `GET /api/player/tracking/steps?from=…&to=…`.
- [x] `HydrationPlayerController` — `GET /hydration/today`, `PUT /hydration/today/target`, `PUT /hydration/target` (player global), `POST /hydration/entries`, `PUT /hydration/entries/{id}`, `DELETE /hydration/entries/{id}`.
- [x] `SleepPlayerController` — `POST /api/player/tracking/sleep`, `PUT /sleep/{id}`, `DELETE /sleep/{id}`, `GET /sleep?from=…&to=…`.
- [x] `WeightPlayerController` — `POST /api/player/tracking/weight`, `PUT /weight/{id}`, `DELETE /weight/{id}`, `GET /weight?from=…&to=…`.
- [x] DataOutputs use the date-string convention (`?->format(\DateTimeInterface::ATOM)`) — already enforced at the UseCase layer (2.4).

### [x] 2.6 Player frontend: dashboard tracking widget
- [x] `frontend/website/src/components/tracking/` — new component folder. **Deviation**: placed under `components/tracking/` (mirrors the on-disk `components/workout/`), not `features/tracking/` — the website has no `features/` dir (pages live in `pages/`, shared components in `components/<domain>/`). Data layer added at `api/endpoints/tracking.ts`, `api/types.ts` (Tracking DTOs), `hooks/tracking/{keys,useTracking}.ts` (TanStack Query, mutations invalidate `trackingKeys.all`).
- [x] `<TrackingWidget />` — composite. **Responsive 2×2 grid** (`grid-cols-1 sm:grid-cols-2`, user-chosen) of `<StepsCard />`, `<HydrationCard />`, `<SleepCard />`, `<WeightCard />`. Each shows the day's value + an inline editor (no modal).
- [x] `<HydrationCard />` — progress bar `amountConsumedMl / targetMl` + a "+ Ajouter" affordance (ml input) + a list of today's entries each with a "Supprimer" button. Inline editable day-target (✎). Uses `GET /hydration/today` (server-side "today", lazy-created).
- [x] `<StepsCard />` — single int input for today's count (reads via `listSteps(today, today)`, writes via `upsertSteps`).
- [x] `<SleepCard />` — `bedAt` + `wakeAt` (`datetime-local`) + `quality` (1–5 emoji selector). Shows last night's record if any (duration + emoji + bed→wake times). Logs if no record for the day, updates otherwise.
- [x] `<WeightCard />` — int/decimal input in kg (UI), converted to grams at the boundary (`Math.round(kg * 1000)`). Logs-or-updates today's entry.
- [x] Mounted on the dashboard (`/`, `pages/workout/DashboardPage.tsx`) **above the upcoming-workouts list** (user-chosen), under the `PageHeader`.
- [x] All styling theme-agnostic (Tailwind v4 CSS-variable utilities only). Reused the `ui` kit (`Card`/`Button`/`Input`/`Label`/`Spinner`/`Alert`). **Note**: the website has no `icon-button` kit (that note in `CLAUDE.md` describes a kit that isn't present on disk here) — action buttons follow the actual on-disk pattern (`<Button variant="ghost" size="sm">` text buttons, as in `SetRow`).
- [x] `npm run typecheck && lint && build` green on `frontend/website` (lint: 0 errors; 4 pre-existing warnings in AuthContext/SetForm, none from the new files).
- [x] **Manual browser validation accepted by the user** (committed the Phase-2 work and moved on, incl. the steps daily-target carded). Backend HTTP path cURL-smoked in 2.5 + the steps-target endpoints in the post-2.6 addition.

### [x] 2.7 Migration + schema HTML
- [x] **Migrations applied** (dev + test): `Version20260505153556` (5 tracking tables + `player.daily_hydration_target_ml`, done in 2.1) and `Version20260613111300` (`player.daily_steps_target` global default 5000 + `steps_daily_entry.target` per-day snapshot, backfilled to 5000). `doctrine:schema:validate` ✅ in sync.
- [x] **Regenerated `specifications/database-schema.html`** for the whole Phase-2 surface: added the 5 tracking tables (`hydration_daily_summary`, `hydration_entry`, `sleep_daily_entry`, `steps_daily_entry`, `weight_entry`) to the TOC + per-table blocks (columns/indexes/FKs taken verbatim from `SHOW CREATE TABLE`), added `player.daily_hydration_target_ml` (1000) + `player.daily_steps_target` (5000) + `steps_daily_entry.target` (5000), and added a **Tracking** lane to the SVG diagram (5 entity boxes, player FKs noted in-box + one `hydration_entry → hydration_daily_summary` arrow, viewBox grown to 740). TOC↔`<h3>` ids verified aligned (17/17), tag balance OK.

### [x] 2.8 Verification
- [x] **Unit tests**: every Tracking Validator under `tests/Unit/Domain/Validator/Player/Tracking/...` (Steps incl. the 2 target validators, Hydration ×4, Sleep ×3, Weight ×3). Happy / each rule / accumulation covered; ownership on edit shapes is gateway-scoped 404 (no `assertPlayerOwns`) per the per-sub-batch deviations.
- [x] **Integration tests**: every Tracking UseCase under `tests/Integration/UseCase/Player/Tracking/...` (manual instantiation + stubbed `LoggedPlayerResolverInterface`).
- [x] **Service tests**: `HydrationAggregateEvaluatorTest` + `SleepDurationEvaluatorTest` (from 2.2).
- [x] cURL smoke: full HTTP path validated for all four metrics incl. lazy-create, aggregate recompute, target override, and 422 on duplicate/non-positive (done across 2.5 + the steps-target addition).
- [x] `composer qa` green (**368 tests / 735 assertions**); `npm run typecheck && lint && build` green on `frontend/website`.

---

## Phase 3 — Leveling sub-domain (entities + admin curve + XP-on-completion + journal + header)

### [x] 3.1 DataModels + Player columns
- [x] Added to `PlayerDataModel`: `int $level` (default 1), `int $currentXp` (default 0), `int $xpToNextLevel` (default **4000** = `1000×2²+0`, the seeded bracket-1 cost for level 2 — recomputed at registration in 3.3). Column defaults backfill existing rows automatically.
- [x] `Domain/DTO/DataModel/Leveling/EarnedExperience/EarnedExperienceDataModel` — `player` (M:1, NOT NULL), `label` (VARCHAR 255), `amount` (INT), `earnedAt` (DATETIME), `sourceType` (VARCHAR 20), `sourceId` (VARCHAR 26), `isLocked` (BOOL default false). Implements `OwnedByPlayerInterface`.
- [x] `Domain/DTO/DataModel/Leveling/LevelBracket/LevelBracketDataModel` — `fromLevel` (INT), `toLevel` (INT nullable), `coefficientA` (INT), `exponentK` (INT), `offsetB` (INT). Unique on `from_level` (`uniq_level_bracket_from_level`).
- [x] `Domain/DTO/DataModel/Leveling/LevelingConfig/LevelingConfigDataModel` — singleton, `public const string LEVELING_CONFIG_ID = '01000000000000000000000000'` (constructor seeds `id` to it). Field `xpPerWorkoutMinute` (INT, default 50). The singleton-aware persist (fixed id, no ULID) is wired in 3.4; the row is seeded in 3.10.
- [x] `Domain/Registry/Leveling/EarnedExperience/EarnedExperienceSourceTypeRegistry` — `QUEST = 'quest'`, `WORKOUT = 'workout'`, `ALL`.
- [x] **Migration `Version20260613114028`** (3 CREATE TABLE + ALTER player ADD 3 columns) applied dev + test; `doctrine:schema:validate` ✅; `composer qa` green (**368 tests / 735 assertions**, no new tests — entities inert until 3.2+). **Schema HTML regen + seed rows deferred to 3.10** (per the dev-plan; flagged in the resume pointer).

### [x] 3.2 Domain service: `LevelingCalculator`
- [x] `Domain/Service/Leveling/LevelingCalculator` (`final readonly`). Constructor: `LevelBracketProviderGateway $bracketProvider`.
- [x] `marginalCostFor(int $level): int` — reloads the curve (`findAllOrderedAsc`), resolves the covering bracket in memory (`fromLevel ≤ level && (toLevel === null || level ≤ toLevel)`), returns `a × level^k + b`; throws `\LogicException` if none matches. Uses an integer-power helper (loop) rather than `**` so the result stays `int` with no cast (avoids the `int**int → int|float` quirk; honors the "no silencing casts" rule).
- [x] `applyEarnedAmount(PlayerDataModel $player, int $earned): void` — loads the curve once, then `currentXp += earned`; while `currentXp ≥ xpToNextLevel`: subtract, `++level`, `xpToNextLevel = cost(level + 1)`. Pure in-memory mutation (caller persists).
- [x] Unit tests (7): marginal cost in bracket #1/#2/#3, accumulate-without-levelup, single roll, multi-level skip with remainder, throws when uncovered. `composer qa` green (**375 tests / 752 assertions**).
- [x] **Pulled forward from 3.4 (needed so the container resolves `LevelingCalculator`)**: created `Domain/Gateway/Provider/Leveling/LevelBracket/LevelBracketProviderGateway` (`findAllOrderedAsc()` only) + its sole impl `Infrastructure/Repository/Leveling/LevelBracket/LevelBracketRepository`. Symfony auto-aliases the interface → repository (verified: same single-implementation autowiring as the Tracking gateways), so the autowired `LevelingCalculator` service compiles. The remaining LevelBracket provider methods (`findContainingLevel`, `findOneByIdForAdminAction`), the persister, and the EarnedExperience/LevelingConfig gateways stay in 3.4.

### [x] 3.3 Player baseline at registration
- [x] Modified `PlayerPersister::create` to set `level=1`, `currentXp=0`, `xpToNextLevel = LevelingCalculator::marginalCostFor(2)`, `dailyHydrationTargetMl=1000` before delegating to `doCreate`. `LevelingCalculator` injected via constructor (4th arg).
- [x] Updated `RegisterPlayerUseCaseTest` happy-path to assert the four baseline columns (`level=1`, `currentXp=0`, `xpToNextLevel=4000`, `dailyHydrationTargetMl=1000`).
- [x] **Brackets/baseline dependency resolved via option (a) — seed early, realised as a data migration** (see deviation below). Pulled forward from 3.10: `Version20260613120000` seeds the 3 baseline `LevelBracket` rows. Also pulled forward `LevelBracketPersisterGateway` + `LevelBracketPersister` (from 3.4) and added `LevelBracketFixtures` (dev seed). `composer qa` green (**375 tests / 756 assertions**, cs ✅, stan ✅).

### [x] 3.4 Gateways + Repositories + Persisters (Leveling entities)
- [x] `EarnedExperienceProviderGateway` + `EarnedExperiencePersisterGateway` + `EarnedExperienceRepository` + `EarnedExperiencePersister`. Provider methods: `findUnlockedBefore(\DateTimeImmutable $cutoff)` (ordered player ASC then earnedAt ASC, for the cron), `findAllByPlayerForJournal(PlayerDataModel, int $page, int $perPage)` (earnedAt DESC, paginated), `countByPlayerForJournal(PlayerDataModel)`, `findOneBySourceTypeAndId(string $sourceType, string $sourceId)` (used by Phase 5's same-day edit propagation). Persister = pass-through triplet.
- [x] `LevelBracketProviderGateway` + `LevelBracketPersisterGateway` + `LevelBracketRepository` + `LevelBracketPersister`. Provider: `findAllOrderedAsc()`, `findContainingLevel(int $level)`, `findOneByIdForAdminAction(string $id)`. `findAllOrderedAsc()` + Repository pulled forward in 3.2, `PersisterGateway` + `Persister` in 3.3; the two extra provider methods added here.
- [x] `LevelingConfigProviderGateway` + `LevelingConfigPersisterGateway` + repository + persister. Provider: `getSingleton(): LevelingConfigDataModel` (queries the well-known fixed id, throws `\LogicException` if missing — seed lands by migration, see deviation). **Persister exposes only `update`** (no create/delete): the singleton's fixed id would be clobbered by the base persister's ULID generation on `doCreate`, and a singleton is never deleted — it is seeded by migration and edited via `update` (Phase 3.7). See deviation below.

### [x] 3.5 Backend: workout COMPLETED → `EarnedExperience` generation
- [x] `FinishWorkoutUseCase` now ends by `awardWorkoutExperience($workout)`: guards `dateEnd ≥ startOfToday(Europe/Paris)` (always true here since `dateEnd = now`, kept explicit for the Phase-5 retro/edit paths), computes `durationMinutes = (int) round((dateEnd − dateStart) / 60)` and `amount = durationMinutes × LevelingConfig.xpPerWorkoutMinute`. When `amount > 0`, creates an unlocked `EarnedExperience` (`sourceType=workout`, `sourceId=workout.id`, `label="Workout: "+name`, `earnedAt=dateEnd`).
- [x] Wired `LevelingConfigProviderGateway` + `EarnedExperiencePersisterGateway` into `FinishWorkoutUseCase` (`ClockInterface` already present).
- [x] **Confirmed**: does not bump `Player.level` / `currentXp` — left to the nightly cron (Phase 5/6).
- [x] `FinishWorkoutDataOutput` gained `?int $earnedXp` (defaults null), returned from the use case. Frontend display deferred (out of 3.5 backend scope).
- [x] **Singleton seed pulled forward from 3.10** (the trap flagged in 3.4's resume pointer): `Version20260613130000` seeds the `LevelingConfig` row so `getSingleton()` resolves in dev + the migrated test DB; `LevelingConfigFixtures` re-seeds it after `fixtures:load` purges (see deviation). Tests: new `testItPersistsAnUnlockedEarnedExperienceForTheCompletedWorkout` + an `earnedXp=3000` assertion on the happy path; `PlayerWorkoutLifecycleTest`'s manual `FinishWorkoutUseCase` build updated for the 2 new args. `composer qa` green (**376 tests / 764 assertions**, cs ✅, stan ✅).

### [x] 3.6 Admin LevelBracket CRUD
**Backend + frontend done.** `composer qa` green (**406 tests / 812 assertions**, cs ✅, stan ✅); admin `typecheck`/`lint`/`build` green.
- [x] UseCases under `UseCase/Admin/Leveling/LevelBracket/`:
  - [x] `CreateLevelBracketUseCase`.
  - [x] `UpdateLevelBracketUseCase`.
  - [x] `DeleteLevelBracketUseCase` (no validator — delete is unconditional, gap-leaving allowed).
  - [x] `ListLevelBracketsUseCase` (`AbstractPublicUseCase`, empty input, returns `findAllOrderedAsc` mapped — no sort/direction since the curve is inherently ordered).
  - [x] `GetLevelBracketDetailsUseCase`.
- [x] Validators: `CreateLevelBracketValidator`, `UpdateLevelBracketValidator`. **One umbrella `ERROR_CODE = 'LEVEL_BRACKET_VALIDATION_FAILED'`** (house style, deviation 1.1) — the named sub-codes from the original plan were dropped; violations accumulate under field keys (`fromLevel`/`toLevel`/`exponentK`) and a `curve` key. Both build the *resulting* curve (existing ± the new/updated bracket) and delegate the structural checks to a new pure domain service **`Domain/Service/Leveling/LevelCurveEvaluator`** (`collectFieldViolations`, `sortByFromLevel`, `collectCurveViolations`) — enforces `fromLevel≥1`, `exponentK≥1`, `toLevel≥fromLevel`, first-bracket-at-1, contiguity/non-overlap, single-open-ended-last, strictly-positive marginal cost at boundaries.
- [x] DataInputs / DataOutputs follow the v0 admin-CRUD shape (`Create/Update/Delete/GetDetails/List…DataInput`, `LevelBracketDataOutput`, `LevelBracketListItemDataOutput`, `DeleteLevelBracketDataOutput {deletedId}`).
- [x] `LevelBracketAdminController` under `Infrastructure/Controller/Admin/Leveling/` — 5 routes under `/api/admin/level-brackets`.
- [x] Tests: 5 UseCase integration tests + `Create`/`Update` validator unit tests + an exhaustive `LevelCurveEvaluatorTest` (all invariants). Tests account for the migration-seeded baseline curve in the test DB (clear-then-create for happy paths; the seeded full curve drives the rejection cases).
- [x] **Frontend admin**: `frontend/admin/src/features/levelBrackets/` — `types.ts`, `api.ts`, `hooks.ts`, `LevelBracketForm.tsx` (5 `InputNumber` fields; empty `toLevel` = open-ended), `LevelBracketListPage.tsx` (table + curve-preview chart), `LevelBracketCreatePage.tsx`, `LevelBracketEditPage.tsx`, plus `LevelCurveChart.tsx`. Routes added in `AppRouter` (`/level-brackets`), menu entry in `AppSider` (`LineChartOutlined`). **Curve preview uses MUI X Charts** (`@mui/x-charts` + `@mui/material` + `@emotion/react`/`@emotion/styled`, per user) — first MUI deps in the otherwise-antd admin; installed in the `frontend-admin` container (also a fresh local-dev fix: `compose.yaml` admin `VITE_API_BASE_URL` default flipped https→http to match the website, since the symfony CA isn't installed here; and a missing global CSS reset `src/index.css` was added — both unrelated to 3.6 but landed this session). Admin `typecheck`/`lint`/`build` green; bundle grew to ~1.57 MB (chunk-size warning is pre-existing and non-blocking).

### [x] 3.7 Admin LevelingConfig (`xpPerWorkoutMinute`)
- [x] `UseCase/Admin/Leveling/LevelingConfig/GetLevelingConfigUseCase` (returns the singleton). Extends `AbstractPublicUseCase` (read-only) with empty `GetLevelingConfigDataInput`, mirroring `GetLevelBracketDetailsUseCase`.
- [x] `UseCase/Admin/Leveling/LevelingConfig/UpdateLevelingConfigUseCase` — updates `xpPerWorkoutMinute`. `UpdateLevelingConfigValidator` (extends `AbstractLoggedAdminValidator`): integer (PHP-typed) + `≥ 50` (const `MIN_XP_PER_WORKOUT_MINUTE`). Error code: `LEVELING_CONFIG_VALIDATION_FAILED`. Loads the singleton via `getSingleton()`, persists via the `update`-only gateway.
- [x] `LevelingConfigAdminController` under `Infrastructure/Controller/Admin/Leveling/` — `GET /api/admin/leveling-config`, `PUT /api/admin/leveling-config` (decodePayload helper, mirrors `LevelBracketAdminController`).
- [x] Frontend admin: `frontend/admin/src/features/levelBrackets/` gained `levelingConfig.ts` (types + api + TanStack hooks, key `['levelingConfig']`) + `LevelingConfigCard.tsx` — a small "Global config" Card with one `InputNumber min={50}` + Save, mounted at the top of `LevelBracketListPage` (no new route/menu). 422 `violations.xpPerWorkoutMinute` surfaced inline via `form.setFields`.
- [x] Tests: `GetLevelingConfigUseCaseTest` (1) + `UpdateLevelingConfigUseCaseTest` (2: happy + below-min rejection) integration; `UpdateLevelingConfigValidatorTest` (5) unit. `composer cs`/`stan` green; in-container phpunit **414 tests / 826 assertions**; admin `typecheck`/`lint`/`build` green. No schema change (table + singleton already exist from 3.5).

### [x] 3.8 Player XP journal
- [x] `UseCase/Player/Leveling/EarnedExperience/ListEarnedExperienceUseCase` — paginated (`{page, perPage}`, `DEFAULT_PER_PAGE=20`, `MAX_PER_PAGE = 50`), ordered by `earnedAt DESC`. Returns `EarnedExperienceJournalDataOutput {items, page, perPage, totalCount}`; each item `id, label, amount, earnedAt(ISO ATOM), sourceType, sourceId, isLocked`. Standalone `ListEarnedExperienceValidator` (error code `LIST_EARNED_EXPERIENCE_VALIDATION_FAILED`, rules page≥1 / perPage∈[1,50]). Cloned from the `ListWorkoutHistoryUseCase` pagination chain; provider methods `findAllByPlayerForJournal` + `countByPlayerForJournal` were already in place (3.4).
- [x] `EarnedExperiencePlayerController::journal` → `GET /api/player/leveling/journal?page=1&perPage=20` (parses query params like `WorkoutPlayerController::history`).
- [x] `frontend/website/src/pages/leveling/XpJournalPage.tsx` (NOT `features/` — website has no such dir, see deviation) — paginated list cloned from `HistoryPage`; each row: `formatDate(earnedAt)` + label + `<Badge tone="primary">+X XP</Badge>` + an inline-SVG lock when `isLocked`. Route `/leveling/journal` in `App.tsx`, linked via a flat `NavBar` `LINKS` entry "Journal XP" (NOT a dropdown — none exists, see deviation). Added `api/endpoints/leveling.ts`, `hooks/leveling/{keys,useLeveling}.ts`, types in `api/types.ts`.
- [x] Tests: `ListEarnedExperienceUseCaseTest` (4: order DESC / pagination / cross-player isolation / perPage>50 reject) + `ListEarnedExperienceValidatorTest` (5). `composer cs`/`stan` green; in-container phpunit **423 tests / 851 assertions**; website `typecheck`/`lint`/`build` green. No schema change.

### [x] 3.9 Player frontend: header progress bar
- [x] Backend: **no profile endpoint existed** → added `GetPlayerProfileUseCase` (`UseCase/Player/Profile/`, empty `GetPlayerProfileDataInput`, no validator — resolves the logged player like `GetTodayHydrationUseCase`) returning `PlayerProfileDataOutput {id, displayName, level, currentXp, xpToNextLevel}`, exposed by `ProfilePlayerController` (`GET /api/player/profile`). Columns already on `PlayerDataModel` (3.1). Integration test asserts the registration baseline (level 1 / currentXp 0 / xpToNextLevel 4000 / displayName).
- [x] Frontend: `<PlayerLevelBadge />` in `frontend/website/src/components/layout/` (the website has no `src/layout/`; layout components live under `components/layout/` alongside `NavBar`/`AppLayout`). Renders `Niv. {level} • {currentXp}/{xpToNextLevel} XP` + a thin progress bar (`role="progressbar"`, width = `currentXp/xpToNextLevel`). Mounted in `NavBar` grouped with the brand on the left (the header is `NavBar`, not a separate dropdown). Added `api/endpoints/profile.ts`, `hooks/profile/{keys,useProfile}.ts`, `PlayerProfileDataOutput` type. Renders `null` until the profile query resolves.
- [x] Styling reuses CSS variables (`--color-primary`, `--color-surface-muted`, `--color-text-muted`, `--text-xs`), parchment-theme consistent.
- [x] `composer cs`/`stan` green; in-container phpunit **424 tests / 856 assertions**; website `typecheck`/`lint`/`build` green. No schema change. NB: `level`/`currentXp` stay at the registration baseline until the Phase 5/6 nightly cron lands — fresh players see `Niv. 1 • 0/4000`.

### [x] 3.10 Migration + seed + schema HTML
**Migrations + seeds were all pulled forward into earlier steps; this step was reduced to the schema-HTML regen.** Table names in the original bullets (`*_data_model`) are stale — the actual tables are `earned_experience`, `level_bracket`, `leveling_config`.
- [x] Migration: 3 new tables (`earned_experience`, `level_bracket`, `leveling_config`) + ALTER `player` (3 new INT columns) — landed as `Version20260613114028` (in 3.1).
- [x] Backfill existing players: handled by the NOT-NULL column **defaults** (`level=1`, `current_xp=0`, `xp_to_next_level=4000`) in `Version20260613114028` — MySQL backfills existing rows on `ADD COLUMN ... DEFAULT`, so no separate `UPDATE` was needed (matches the 3.1 approach for the tracking target columns).
- [x] Seed inserts: 3 `LevelBracket` rows via `Version20260613120000` (3.3) + 1 `LevelingConfig` singleton via `Version20260613130000` (3.5). (Realised as separate data migrations rather than folded into the schema migration — see the 3.3 / 3.5 deviations.)
- [x] Regenerate `specifications/database-schema.html` — added the 3 Leveling tables (TOC + per-table blocks with columns/indexes/FKs from `SHOW CREATE TABLE`), the 3 `player` columns (`level`/`current_xp`/`xp_to_next_level`), and a **Leveling** lane to the SVG diagram (3 entity boxes; `earned_experience` FK→player noted in-box, `level_bracket`/`leveling_config` FK-less; viewBox grown to `880`). TOC↔`<h3>` ids verified aligned (20/20), tag balance OK, `doctrine:schema:validate` still in sync.

### [x] 3.11 Verification
**Audit of Phase-3 coverage — almost everything was already in place from the per-step work; only the 0-minute XP case was missing and was added here.**
- [x] Unit tests present: `LevelingCalculatorTest` + `LevelCurveEvaluatorTest`; every Leveling validator (`CreateLevelBracketValidatorTest`, `UpdateLevelBracketValidatorTest`, `UpdateLevelingConfigValidatorTest`, `ListEarnedExperienceValidatorTest`); every admin Leveling UC integration test (5 `LevelBracket*` + `Get`/`Update LevelingConfig`).
- [x] Player UC integration test `ListEarnedExperienceUseCaseTest` covers pagination, `earnedAt DESC` ordering, and cross-player isolation (3.8).
- [x] `FinishWorkoutUseCaseTest`: 60-minute → 3000-XP unlocked `EarnedExperience` (`sourceType=workout`, `isLocked=false`) was already asserted (3.5); **added `testItGrantsNoExperienceForAZeroMinuteWorkout`** — `dateStart = now` ⇒ rounded duration 0 ⇒ `earnedXp === null` and no `EarnedExperience` row (parameterised the seed helper's `dateStart`).
- [x] cURL/admin smokes: covered equivalently by the integration suite (workout-finish → `EarnedExperience`, journal pagination, bracket-contiguity 422, `xpPerWorkoutMinute < 50` 422). The `level`/`currentXp` untouched-until-cron behaviour is asserted by the absence of any level mutation in `FinishWorkoutUseCase` (cron is Phase 5/6). Manual cURL not re-run this session beyond the `--no-tls` login smoke.
- [x] `composer cs`/`stan` green; in-container phpunit **425 tests / 858 assertions**; both frontends (`admin` + `website`) `typecheck`/`lint`/`build` green. **Phase 3 (Leveling) is complete.**

---

## Phase 4 — Questing sub-domain

### [ ] 4.1 DataModels + Registries
- [ ] `Domain/DTO/DataModel/Questing/Quest/QuestDataModel` — `label`, `kind` (string enum), `metric` (nullable string enum), `periodicity` (string enum), `targetValue` (nullable float — `Type::DECIMAL` (12, 4) `?string` per v0 numeric-string convention; revisit if the user wants float instead), `dateStart` (DATETIME), `dateEnd` (nullable DATETIME), `rewardedXP` (INT > 0).
- [ ] `Domain/DTO/DataModel/Questing/QuestProgression/QuestProgressionDataModel` — `quest` (M:1), `player` (M:1), `startDate` (nullable DATETIME), `endDate` (nullable DATETIME), `completionDate` (nullable DATETIME), `claimedDate` (nullable DATETIME), `currentValue` (nullable string, same numeric-string convention), `status` (string enum). Unique on `(quest_id, player_id, startDate)` — `startDate` is null-comparable in MySQL with the standard NULL-distinct semantics, which is exactly what we want (one `unique` progression per player/quest pair, multiple per period otherwise).
- [ ] Registries:
  - [ ] `Domain/Registry/Questing/Quest/QuestKindRegistry` — `AUTOMATIC`, `MANUAL`.
  - [ ] `Domain/Registry/Questing/Quest/QuestPeriodicityRegistry` — `UNIQUE`, `DAILY`, `WEEKLY`, `MONTHLY`.
  - [ ] `Domain/Registry/Questing/Quest/QuestMetricRegistry` — `STEPS_DAILY`, `HYDRATION_ML_DAILY`, `SLEEP_DURATION_MINUTES`, `WORKOUT_COUNT`, `WORKOUT_DURATION_MINUTES`.
  - [ ] `Domain/Registry/Questing/QuestProgression/QuestProgressionStatusRegistry` — `IN_PROGRESS`, `CLAIMABLE`, `REWARDED`.

### [ ] 4.2 Gateways + Repositories + Persisters
- [ ] `QuestProviderGateway` — `findActiveAtForList(\DateTimeImmutable $now)` (filters by `dateStart ≤ now AND (dateEnd IS NULL OR dateEnd ≥ now)`), `findActiveByPeriodicityForPlayer(string $periodicity, \DateTimeImmutable $now)`, `findOneByIdForAdminAction`.
- [ ] `QuestPersisterGateway` — standard `create`/`update`/`delete`.
- [ ] `QuestProgressionProviderGateway` — `findOneByPlayerQuestPeriod(player, quest, ?startDate)`, `findAllByPlayerActiveDaily/Weekly/Monthly(player, now)`, `findAllUniqueByPlayer(player)`, `findOneByIdForPlayerAction(id, player)` (player-scoped 404).
- [ ] `QuestProgressionPersisterGateway` — standard.
- [ ] `QuestProgressionDataModel` implements `OwnedByPlayerInterface` (direct `player` property).

### [ ] 4.3 Domain services
- [ ] `Domain/Service/Questing/QuestPeriodResolver`:
  - `resolve(string $periodicity, \DateTimeImmutable $now): array{startDate: ?\DateTimeImmutable, endDate: ?\DateTimeImmutable}` — daily: today 00:00 / 23:59:59 in app TZ (Europe/Paris); weekly: ISO Monday / Sunday 23:59:59; monthly: 1st / last day 23:59:59; unique: `[null, null]`.
- [ ] `Domain/Service/Questing/QuestProgressionFactory`:
  - `findOrCreate(QuestDataModel $quest, PlayerDataModel $player, \DateTimeImmutable $now): QuestProgressionDataModel` — uses `QuestPeriodResolver` to compute the period, calls the provider gateway with `(player, quest, startDate)`. If no row exists, creates one with the proper default status (`CLAIMABLE` for `manual`, `IN_PROGRESS` for `automatic`) and `currentValue=0` for `automatic` quests.
- [ ] `Domain/Service/Questing/QuestProgressionEvaluator`:
  - `refreshFor(PlayerDataModel $player, string $metric, \DateTimeImmutable $now): void` — finds active `automatic` quests with `Quest.metric === $metric`, locates each one's current-period `QuestProgression` (find-or-create), recomputes `currentValue` from the underlying tracking source (delegates to a per-metric strategy: `StepsDailyMetricResolver`, `HydrationMlDailyMetricResolver`, etc., all in `Domain/Service/Questing/MetricResolver/`), and transitions to `CLAIMABLE` when `currentValue ≥ targetValue` (sets `completionDate=$now`).
  - Per-metric resolvers each implement a small interface `MetricResolverInterface::resolveCurrentValue(PlayerDataModel, \DateTimeImmutable startDate, \DateTimeImmutable endDate): float`. They depend only on the Tracking provider gateways + the Workout provider gateway.

### [ ] 4.4 Auto-progression hooks
- [ ] In every Tracking write-path UseCase (`AddHydrationEntry`, `UpsertStepsForDay`, `LogSleep`, etc.), call `QuestProgressionEvaluator::refreshFor($player, $metric, $now)` after the tracking write succeeds. Multiple metrics may be affected by a single write (e.g. `LogSleep` only refreshes `SLEEP_DURATION_MINUTES`).
- [ ] In `FinishWorkoutUseCase`, call `refreshFor($player, 'WORKOUT_COUNT', $now)` and `refreshFor($player, 'WORKOUT_DURATION_MINUTES', $now)`.
- [ ] Document the hook list as a checklist in `CLAUDE.md` so future tracking metrics don't forget the wiring.

### [ ] 4.5 Player UseCases
Under `UseCase/Player/Questing/`. Player-edit validators extend `AbstractLoggedPlayerValidator`.
- [ ] `ListDailyQuestsUseCase`, `ListWeeklyQuestsUseCase`, `ListMonthlyQuestsUseCase` — for each: load active quests of the periodicity, find-or-create the matching `QuestProgression` per quest, return a list of `QuestProgressionDataOutput` (label, kind, metric, currentValue, targetValue, status, claimable-at-utility flags).
- [ ] `ListUniqueQuestsUseCase` — same shape, single bucket.
- [ ] `TickManualQuestUseCase` — `(progressionId)` → must be `manual`, status `CLAIMABLE` (default for `manual`) → no-op (manual quests are already claimable by default; the use case really just exposes the affordance — keep it for consistency / to allow future "I attempted this" semantics if needed). **Decision pending — confirm with user during 4.5 implementation that manual quests don't actually need a "tick" step since their default status is already `CLAIMABLE`.**
- [ ] `ClaimQuestRewardUseCase` — `(progressionId)`. Validator: ownership; `status === CLAIMABLE`; rewards within active window (`Quest.dateEnd` not past). On success: `claimedDate=$now`, `status=REWARDED`, create `EarnedExperience` (`sourceType=quest`, `sourceId=progressionId`, `label="Quest: <quest.label>"`, `amount=quest.rewardedXP`, `earnedAt=$now`, `isLocked=false`). Returns `ClaimQuestRewardDataOutput {progressionId, earnedExperienceId, amount}`.

### [ ] 4.6 Player REST + frontend widget
- [ ] `QuestPlayerController` under `Infrastructure/Controller/Player/Questing/`:
  - `GET /api/player/quests/daily`, `/weekly`, `/monthly`, `/unique`.
  - `POST /api/player/quests/{progressionId}/claim`.
- [ ] Frontend `<QuestWidget />` on the dashboard. Three tabs (`Daily`, `Weekly`, `Monthly`); active tab controlled by URL hash for shareability. Daily tab shows progress bars (`currentValue / targetValue`). Weekly / Monthly show flat lists. `<QuestRow />` renders the label + a Claim button when `status === CLAIMABLE`.
- [ ] Empty state per tab: "No active quests for this period." when the list is empty.

### [ ] 4.7 Player REST + frontend: unique quests page
- [ ] `QuestPlayerController::listUnique` already covered above.
- [ ] Frontend `/quests/unique` page (linked from the header dropdown). Three sections: "Available" (`IN_PROGRESS` automatic + `CLAIMABLE` manual), "Ready to claim" (`CLAIMABLE` automatic), "Completed" (`REWARDED`). Reuses `<QuestRow />`.

### [ ] 4.8 Admin Quest CRUD
Under `UseCase/Admin/Questing/Quest/`. Validators extend `AbstractLoggedAdminValidator`.
- [ ] `CreateQuestUseCase`, `UpdateQuestUseCase`, `DeleteQuestUseCase`, `ListQuestsUseCase`, `GetQuestDetailsUseCase`.
- [ ] Validator rules:
  - `kind` ∈ `{automatic, manual}`.
  - `metric` non-null iff `kind === automatic`; otherwise null. Error code `KIND_METRIC_MISMATCH_CODE`.
  - `targetValue` non-null when `kind === automatic` (and `metric` is set); null when `kind === manual`. Error code `TARGET_VALUE_MISMATCH_CODE`.
  - `periodicity` ∈ `{unique, daily, weekly, monthly}`.
  - `dateStart` defaults to `$now` if not provided in `CreateQuestDataInput`.
  - `dateEnd` (when present) is strictly after `dateStart`.
  - `rewardedXP > 0`.
  - On Update: when changing `kind`, the same invariants must hold for the new combination; existing in-flight `QuestProgression` rows are not retroactively changed (admin must explicitly delete them if needed — out of v1 scope).

### [ ] 4.9 Admin REST + admin frontend
- [ ] `QuestAdminController` under `Infrastructure/Controller/Admin/Questing/` — standard 5 routes under `/api/admin/quests`.
- [ ] `frontend/admin/src/features/quests/` — `QuestListPage`, `QuestCreatePage`, `QuestEditPage`. Form: `<Select>` for `kind`, conditional `<Select>` for `metric`, conditional `<InputNumber>` for `targetValue` (mirror the backend `kind ⇔ metric ⇔ targetValue` invariants client-side; backend stays the source of truth).

### [ ] 4.10 Migration + schema HTML
- [ ] 2 new tables (`quest_data_model`, `quest_progression_data_model`).
- [ ] Apply on dev + test DBs. Regenerate `specifications/database-schema.html`.

### [ ] 4.11 Verification
- [ ] Validator unit tests + UseCase integration tests as per the v0 norm.
- [ ] Service unit tests on `QuestPeriodResolver`, `QuestProgressionFactory`, `QuestProgressionEvaluator` + each `MetricResolver`.
- [ ] **End-to-end test** `tests/Integration/UseCase/Player/Questing/AutomaticQuestLifecycleTest`: seed a `HYDRATION_ML_DAILY` daily quest with `targetValue=1000` → log 3 hydration entries totalling 1500 mL → assert `QuestProgression.status === CLAIMABLE` → claim → assert `status === REWARDED`, `EarnedExperience` row exists with `amount = quest.rewardedXP`, `sourceType=quest`.
- [ ] cURL smoke per route. `composer qa` green; both frontends green.

---

## Phase 5 — Cron Leveling + workout-side locking impacts

This phase closes the locking story. Every constraint here flows from the locking contract; subsections are ordered so each can be tested in isolation.

### [ ] 5.1 `EarnedExperience.isLocked` guard
- [ ] `EarnedExperiencePersister::update` and `::delete` reject calls with `$model->isLocked === true` by throwing `ValidationException` with `errorCode: EARNED_EXPERIENCE_LOCKED`.
- [ ] No use case in v1 is allowed to mutate a locked `EarnedExperience`. The admin lock-lifting capability is explicitly deferred (per requirements §Cron job → Leveling).

### [ ] 5.2 Workout `deleted` status
- [ ] `WorkoutStatusRegistry::DELETED = 'deleted'` added to the registry.
- [ ] Every workout read gateway method (`findOneByIdForPlayerAction`, `findOneByIdForDetails`, `findCompletedByPlayer`, `findPlannedOrInProgressByPlayer`, `findByPlayerForMonth`, `findInProgressByPlayer`) gets a default `AND w.status != 'deleted'` clause.
- [ ] Same default filter on `Domain/Service/PersonalBestEvaluator` (a deleted workout shouldn't influence PB recomputes — not currently called retroactively, but defence in depth).
- [ ] Frontend filter: nothing to do — the backend already excludes them.

### [ ] 5.3 Workout same-day delete: hard delete + cascade
- [ ] New `DeleteWorkoutUseCase` under `UseCase/Player/Training/Workout/` (replaces / extends the existing `CancelWorkoutUseCase` semantics? — verify on disk: v0 has `CancelWorkoutUseCase` for PLANNED/IN_PROGRESS → CANCELED; we need a separate "delete" path).
- [ ] Validator: ownership + `dateStart` (or `dateEnd` if present, or `plannedAt`) falls within today (Europe/Paris). When the test passes → hard delete via `WorkoutPersister::delete`, plus `EarnedExperiencePersister::delete` for the matching `(sourceType=workout, sourceId=workout.id)` entry **only if it is unlocked** (it always is, since same-day → not yet cron'd).

### [ ] 5.4 Workout past-day delete: soft-delete
- [ ] Same `DeleteWorkoutUseCase`, branch when the date check fails: instead of `WorkoutPersister::delete`, transition `status=deleted` via `WorkoutPersister::update`. The locked `EarnedExperience` (if any) is preserved.
- [ ] Update `DeleteWorkoutDataOutput` to surface which path was taken (`{deletedId, mode: "hard" | "soft"}`).
- [ ] Frontend `<DeleteWorkoutButton />` confirms with the mode-specific copy ("This will permanently delete the workout." vs "This will mark the workout as deleted; XP earned is preserved.").

### [ ] 5.5 Workout same-day edit propagation to `EarnedExperience.amount`
- [ ] When a same-day workout's duration changes (the only field that affects XP — extending / shortening), the matching unlocked `EarnedExperience.amount` must be recomputed.
- [ ] Affected use cases: any UC that mutates an `IN_PROGRESS → IN_PROGRESS` workout's start/end time. For v1, the only path that affects duration after `COMPLETED` is **none directly** (we don't currently expose "edit a finished workout"). The trigger is therefore on the `FinishWorkoutUseCase` re-finish edge case, which doesn't exist either. **Decision**: in v1, `EarnedExperience.amount` is set once by `FinishWorkoutUseCase` and never recomputed. If the user later adds an "edit completed workout" UC, that UC must call `EarnedExperiencePersister::update` for the unlocked entry.
- [ ] Document the invariant in `CLAUDE.md`: any future "edit a completed workout same-day" UC owns the recompute.

### [ ] 5.6 Workout retroactive creation: no XP
- [ ] Already covered in Phase 3.5 by the `dateEnd ≥ startOfToday(Europe/Paris)` guard. Verify the guard also handles `PlanWorkoutUseCase` + `StartPlannedWorkoutUseCase + FinishWorkoutUseCase` chains where the `dateStart` is in the past but `dateEnd` is today (those should still earn XP — the rule is on `dateEnd`, not `dateStart`).
- [ ] Add an integration test: complete a workout with `dateStart=yesterday 23:00, dateEnd=today 00:30` → 30 minutes × 50 XP = 1500 XP earned (today's entry).
- [ ] Add an integration test: complete a workout with `dateEnd=yesterday 23:00` (artificially past) → no `EarnedExperience` created.

### [ ] 5.7 Console command `app:leveling:lock-yesterday`
- [ ] `Infrastructure/Command/Leveling/LockYesterdayCommand` (Symfony console). Single responsibility, idempotent.
- [ ] Reads cutoff = `today 00:00:00 Europe/Paris` (via `ClockInterface::now()->setTimezone(Europe/Paris)`).
- [ ] Selects all unlocked `EarnedExperience` with `earnedAt < cutoff`. Groups by `player_id`. For each player, sums the amounts and calls `LevelingCalculator::applyEarnedAmount($player, $sum)`. Persists via `PlayerPersister::update`. Sets `isLocked=true` on each consumed entry via `EarnedExperiencePersister::update` (the locking guard from 5.1 is bypassed here because the persister-level check is on the *prior* state, not the new one — verify that the guard checks `$existing->isLocked`, not `$incoming->isLocked`, before allowing the update).
- [ ] Symfony command outputs a summary (`{playersTouched, entriesLocked, totalXpAwarded}`).

### [ ] 5.8 Cron scheduling
- [ ] `config/scheduler.yaml` (Symfony Scheduler component if installed) or a system crontab note in `README.md`. Schedule: `0 1 * * *` Europe/Paris (1 AM local — gives a 1-hour buffer past midnight for clock skew).
- [ ] Document the production wiring requirement (host crontab vs. supervisor vs. Symfony Scheduler) in `CLAUDE.md` so the deployment phase (v0 leftover Phase 8) hooks it up properly.

### [ ] 5.9 Verification
- [ ] **Unit tests**: `LockYesterdayCommandTest` — empty queue (no-op), single-player single-entry, multi-player multi-entry, entries already locked are skipped, entries earned today are skipped.
- [ ] **Integration test**: `tests/Integration/Command/Leveling/LockYesterdayCommandTest` — end-to-end against the real DB: seed a player + 2 unlocked entries dated yesterday → run the command via `Symfony\Component\Console\Tester\CommandTester` → assert player's `level` / `currentXp` / `xpToNextLevel` updated, both entries `isLocked=true`.
- [ ] cURL smoke: complete a workout today → `EarnedExperience` is unlocked, journal shows it without lock icon → manually invoke the command via `php bin/console app:leveling:lock-yesterday --cutoff=2026-05-06T00:00:00+02:00` (debug-only flag for testability) — verify the entry is now locked and the player's level columns advanced.
- [ ] `composer qa` green; both frontends green.

---

## Phase 6 — Statistiques placeholder

### [ ] 6.1 Player frontend menu + empty page
- [ ] Add "Statistiques" entry to the header nav (top level next to "Dashboard" and "Training ▾"). No dropdown.
- [ ] `frontend/website/src/features/statistics/StatisticsPage.tsx` — empty placeholder with copy: "Cette section accueillera bientôt les graphiques de votre activité. Reviens vite !" (or English equivalent if the user prefers — match existing copy).
- [ ] Route `/statistics` (or `/statistiques` to match the menu — pick one and stay consistent with the rest of the routes; v0 uses English routes throughout, so default to `/statistics`).
- [ ] No backend, no DataModel, no migration.

---

## Phase 7 — Hardening

### [ ] 7.1 Coverage check
- [ ] Verify every new concrete `App\UseCase\...UseCase` has its integration test under `tests/Integration/UseCase/...`. Run `vendor/bin/phpstan analyse` to spot orphan UCs.
- [ ] Verify every new non-abstract `App\Domain\Validator\...Validator` has its unit test under `tests/Unit/Domain/Validator/...`.
- [ ] Verify every new stateful `App\Domain\Service\...` has its unit test under `tests/Unit/Domain/Service/...`. Stateless one-liners (e.g. weight date derivation in the persister) don't need a separate test — they're exercised via the persister's integration test.

### [ ] 7.2 CI run + composer qa green
- [ ] Push a commit and watch `.github/workflows/ci.yml` run all three jobs (`backend`, `frontend-admin`, `frontend-website`) green. Required: cache:warmup of dev container before `composer qa` (already in the workflow per v0 close-out; verify it still runs).
- [ ] If new dependencies were added to either frontend, ensure `package-lock.json` is committed.
- [ ] Final `composer qa` on a clean checkout: cs ✅, stan ✅, phpunit ✅. Final `npm run typecheck && lint && build` on each frontend ✅.

### [ ] 7.3 CLAUDE.md update
- [ ] Append a "v1 close-out" block to `CLAUDE.md` mirroring the v0 close-out style: list of phases shipped, key invariants (auto-progression hook checklist, locking rules, soft-delete, retro rule), pointers to the new Domain services, the LevelingConfig singleton, the LevelBracket validation rules, and any new conventions captured during execution.
- [ ] Reference `specifications/v1/dev-plan.md` as the new source of truth for "what's done"; the v0 close-out section stays for historical context but is no longer the working contract.

### [ ] 7.4 README update (if needed)
- [ ] Update the seeded data section if it has changed (v1 adds a `LevelingConfig` row + 3 `LevelBracket` rows).
- [ ] Add a brief section on the cron command (`app:leveling:lock-yesterday`) and the Tracking / Leveling / Questing sub-domains.

---

## Critical files and directories that will be created or touched

- `src/Domain/DTO/DataModel/{Tracking,Leveling,Questing}/**`
- `src/Domain/Gateway/{Provider,Persister}/{Tracking,Leveling,Questing}/**`
- `src/Domain/Registry/{Leveling,Questing}/**`
- `src/Domain/Service/{Tracking,Leveling,Questing}/**` — `HydrationAggregateEvaluator`, `SleepDurationEvaluator`, `LevelingCalculator`, `QuestPeriodResolver`, `QuestProgressionFactory`, `QuestProgressionEvaluator`, per-metric resolvers.
- `src/Domain/Validator/{Player,Admin}/{Tracking,Leveling,Questing}/**`
- `src/Infrastructure/{Repository,Persister}/{Tracking,Leveling,Questing}/**`
- `src/Infrastructure/Controller/{Player,Admin}/{Tracking,Leveling,Questing}/**`
- `src/Infrastructure/Command/Leveling/LockYesterdayCommand.php`
- `src/UseCase/{Player,Admin}/{Tracking,Leveling,Questing}/**`
- `migrations/Version2026*` — multiple (one per phase touching the schema).
- `specifications/database-schema.html` (regenerated per migration).
- `frontend/admin/src/features/{movements,levelBrackets,quests}/**` (Movement form gains 2 fields; LevelBrackets + Quest CRUD UIs).
- `frontend/website/src/features/{tracking,leveling,questing,statistics}/**` + `frontend/website/src/layout/**` (header progress bar).
- `tests/Unit/**` and `tests/Integration/**` — coverage-mandatory.
- `CLAUDE.md`, `README.md` — appended/updated at Phase 7.

## End-to-end verification matrix

| Phase | How to verify | Status |
|---|---|---|
| 0 | `composer qa` green; doctrine schema in sync; both frontends build | [ ] |
| 1 | Movement create/update with URLs round-trips; player workout view renders the links/GIF | [ ] |
| 2 | Tracking dashboard widget edits all 4 trackers; aggregates auto-update; per-day uniqueness enforced | [ ] |
| 3 | Finishing a workout creates an unlocked `EarnedExperience`; Leveling admin curve + `xpPerWorkoutMinute` editable; XP journal page paginated; header progress bar live | [ ] |
| 4 | Daily/weekly/monthly widget tabs; automatic quests progress as tracking entries land; manual quests claimable; unique quests page lists all states; admin Quest CRUD enforces invariants | [ ] |
| 5 | Cron locks yesterday's entries; level/XP advance on Player; same-day delete = hard; past-day delete = soft (`status=deleted`); retro workout = 0 XP | [ ] |
| 6 | Statistiques menu visible; clicking it shows the placeholder page | [ ] |
| 7 | CI green on a clean clone; coverage parity with v0 baseline maintained; CLAUDE.md / README reflect v1 | [ ] |
