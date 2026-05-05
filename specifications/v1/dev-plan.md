# Akhilleus 2026 — v1 Development Plan

## Resume pointer (last session snapshot)

- **v1 not started yet.** All phases below are `[ ]`. The v0 plan (`specifications/v0/dev-plan.md`) is closed; the codebase you inherit is the result of executing it. Do not rebuild what's already there — always read the on-disk reality (`composer.json`, `src/`, `frontend/{admin,website}/src/`, plus `CLAUDE.md`'s "v0 / MVP state" summary) before scaffolding.
- **Next pending step**: Phase 0.1 (pre-flight) — verify `composer qa` is green, the doctrine schema validates, and both frontends build cleanly on a fresh checkout. This sets the v1 baseline.
- **v0 leftover non-blockers** carried over (out of v1 scope unless the user asks otherwise): Phase 7 manual Chrome/Firefox smoke; Phase 8 prod Dockerfile + `compose.prod.yaml` (deferred until the hosting target is chosen).
- The "Decisions / deviations" block below is empty at v1 start; populate it as we go (anything that materially deviates from `specifications/conventions.md` or this plan).
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

_(Empty at v1 start. Append entries as they happen, in the same idiom as the v0 block — the line that introduced each deviation, the why, and how to roll back. Each new "Decisions / deviations" entry is part of the working contract for future sessions.)_

## Tracking (checkbox convention)

- The dev-plan uses `[x]` / `[ ]` checkboxes on every subsection header **and** every leaf bullet. Tick items off as soon as the step closes. This is the source of truth for "what's done."
- `[~]` marks a subsection where some leaf bullets are still `[ ]` (partial — keep until every leaf is `[x]`).

---

## Phase 0 — Foundation & v1 alignment

### [ ] 0.1 Pre-flight on the inherited codebase
- [ ] On a fresh `composer install` + `npm ci` (both frontends), confirm `composer qa` green: cs ✅, stan ✅, phpunit ✅ (≥ 241 tests / 507 assertions per the v0 close-out).
- [ ] `php bin/console doctrine:schema:validate` returns "in sync" against the dev DB.
- [ ] `npm run typecheck && lint && build` green on `frontend/admin` and `frontend/website`.
- [ ] Note any drift from the v0 baseline (newer dep versions, breaking changes) before starting v1 work.

### [ ] 0.2 Sub-domain scaffolding stubs
Create empty folders so subsequent phases just drop files in. Nothing is committed until the entity it hosts lands (Phase 1+).
- [ ] `src/Domain/DTO/DataModel/{Tracking,Leveling,Questing}/`
- [ ] `src/Domain/Gateway/{Provider,Persister}/{Tracking,Leveling,Questing}/`
- [ ] `src/Domain/Registry/{Leveling,Questing}/` (Tracking has no registry — values are not enums).
- [ ] `src/Domain/Service/{Tracking,Leveling,Questing}/` for the aggregate / calculator services.
- [ ] `src/Domain/Validator/{Player,Admin}/{Tracking,Leveling,Questing}/`
- [ ] `src/Infrastructure/{Repository,Persister}/{Tracking,Leveling,Questing}/`
- [ ] `src/Infrastructure/Controller/{Player,Admin}/{Tracking,Leveling,Questing}/`
- [ ] `src/UseCase/{Player,Admin}/{Tracking,Leveling,Questing}/`
- [ ] `src/Domain/DTO/{DataInput,DataOutput}/{Player,Admin}/{Tracking,Leveling,Questing}/`

### [ ] 0.3 Verification
- [ ] `composer qa` green (no new tests yet — empty folders are inert).
- [ ] `php bin/console debug:container --env=dev` runs without complaints.

---

## Phase 1 — Movement evolutions (`videoLink` + `gifLink`)

### [ ] 1.1 DataModel + admin DTOs
- [ ] Add nullable `?string $videoLink` and `?string $gifLink` columns to `MovementDataModel` (`Type::STRING`, length 2048, nullable). Both default to `null`.
- [ ] Update `Domain/DTO/DataInput/Admin/Training/Movement/CreateMovementDataInput` and `UpdateMovementDataInput` to accept the two optional URL fields.
- [ ] Update `Domain/DTO/DataOutput/Admin/Training/Movement/MovementDataOutput` (and `MovementListItemDataOutput` if it carries them — keep the list slim, probably skip there).
- [ ] Update `CreateMovementValidator` and `UpdateMovementValidator`: when non-null, both fields must pass `filter_var(..., FILTER_VALIDATE_URL)`. New error code constants `INVALID_VIDEO_LINK_CODE` / `INVALID_GIF_LINK_CODE`.

### [ ] 1.2 Admin path: REST + frontend
- [ ] `MovementAdminController::create` and `::update` already pipe DataInput → UseCase → DataOutput; no controller change beyond the new fields surfacing in the JSON shape.
- [ ] `frontend/admin/src/features/movements/MovementForm.tsx`: add two `<input type="url">` fields, mapped to backend `violations.videoLink` / `violations.gifLink`.
- [ ] `MovementListPage`: do not show the URLs in the list (keeps the table tight).

### [ ] 1.3 Player workout view: render the URLs
- [ ] Surface `videoLink` and `gifLink` on `Domain/DTO/DataOutput/Player/Training/Workout/ExerciseMovementDataOutput` (already returned by `GetWorkoutDetailsUseCase` + nested in details / live editor / planned / read-only views).
- [ ] `frontend/website` — extend `<ExerciseEditor>`, `<PlannedWorkoutView>`, `<ReadOnlyWorkoutView>`: when a movement has a `videoLink`, render a small "▶︎ Voir la démo" link (`<a target="_blank" rel="noopener noreferrer">`); when it has a `gifLink`, render a small thumbnail (`<img>`) on click-to-expand. Both inert when null.

### [ ] 1.4 Migration + schema HTML
- [ ] `php bin/console make:migration` → review/clean the generated migration (only ALTER TABLE on `movement_data_model`, two nullable VARCHAR(2048)).
- [ ] Apply on dev + test DBs.
- [ ] Regenerate `specifications/database-schema.html` and commit alongside the migration.

### [ ] 1.5 Verification
- [ ] Validator unit tests: happy path, invalid URL on `videoLink`, invalid URL on `gifLink`, accumulation, both null OK.
- [ ] Integration tests: `Create/UpdateMovementUseCaseTest` exercising the new fields (happy + invalid URL).
- [ ] cURL smoke: admin login → create a movement with both URLs → list shows new fields → update with bad URL → 422 with violations.
- [ ] Frontend manual: admin edits a movement, fills both URLs; player opens the workout details and sees "Voir la démo" + GIF.
- [ ] `composer qa` green; `npm run typecheck && lint && build` green on both frontends.

---

## Phase 2 — Tracking sub-domain

Goal: `Steps`, `Hydration`, `Sleep`, `Weight` end-to-end (DataModel → use cases → REST → dashboard widget) with the snapshot/lazy/aggregate semantics from `specifications/v1/initial-requirements.md`.

### [ ] 2.1 DataModels + Player change
- [ ] Add `int $dailyHydrationTargetMl` (non-null, default 1000) on `PlayerDataModel`.
- [ ] `Domain/DTO/DataModel/Tracking/Steps/StepsDailyEntryDataModel` — `player` (M:1), `date` (DATE), `count` (INT ≥ 0). Unique index on (`player_id`, `date`).
- [ ] `Domain/DTO/DataModel/Tracking/Hydration/HydrationDailySummaryDataModel` — `player`, `date`, `targetMl` (INT non-null, snapshotted at create time from `Player.dailyHydrationTargetMl`), `amountConsumedMl` (INT non-null, default 0, auto-derived). Unique on (`player_id`, `date`).
- [ ] `Domain/DTO/DataModel/Tracking/Hydration/HydrationEntryDataModel` — `summary` (M:1 `HydrationDailySummaryDataModel`), `loggedAt` (DATETIME), `valueMl` (INT > 0).
- [ ] `Domain/DTO/DataModel/Tracking/Sleep/SleepDailyEntryDataModel` — `player`, `date` (DATE — wake-up date), `bedAt` (DATETIME), `wakeAt` (DATETIME), `durationMinutes` (INT, auto-derived), `quality` (nullable INT 1–5). Unique on (`player_id`, `date`).
- [ ] `Domain/DTO/DataModel/Tracking/Weight/WeightEntryDataModel` — `player`, `loggedAt` (DATETIME), `valueGrams` (INT > 0), `date` (DATE, auto-derived from `loggedAt` in the persister). Unique on (`player_id`, `date`).
- [ ] All five new DataModels carry `createdAt` / `updatedAt` per the `AbstractBaseMysqlPersister` contract.

### [ ] 2.2 Aggregate / derivation services
- [ ] `Domain/Service/Tracking/Hydration/HydrationAggregateEvaluator::recompute(HydrationDailySummaryDataModel $summary): HydrationDailySummaryDataModel` — sums all `HydrationEntry.valueMl` linked to `$summary` into `$summary->amountConsumedMl`. Mutates in place + returns the same instance (mirrors `WorkoutAggregateEvaluator`). Triggered by `HydrationEntryPersister::create / update / delete` and by `HydrationDailySummaryPersister::update`.
- [ ] `Domain/Service/Tracking/Sleep/SleepDurationEvaluator::recompute(SleepDailyEntryDataModel $entry): SleepDailyEntryDataModel` — `durationMinutes = floor((wakeAt − bedAt) / 60)`. Validator guards against `wakeAt ≤ bedAt`. Triggered by `SleepDailyEntryPersister::create / update`.
- [ ] Weight date derivation lives directly in `WeightEntryPersister::create / update` (`$model->date = \DateTimeImmutable::createFromInterface($model->loggedAt)->setTime(0,0,0)`) — no separate service for a one-line derivation.

### [ ] 2.3 Gateways + Repositories + Persisters
For each of the five new entities (`StepsDailyEntry`, `HydrationDailySummary`, `HydrationEntry`, `SleepDailyEntry`, `WeightEntry`):
- [ ] `Domain/Gateway/Provider/Tracking/{Entity}/{Entity}ProviderGateway` — context-named methods only (`findOneByPlayerAndDate`, `findAllByPlayerForDateRange`, etc.). No generic finders.
- [ ] `Domain/Gateway/Persister/Tracking/{Entity}/{Entity}PersisterGateway` — `create`, `update`, `delete` typed per `DataModel`.
- [ ] `Infrastructure/Repository/Tracking/{Entity}/{Entity}Repository implements {Entity}ProviderGateway`.
- [ ] `Infrastructure/Persister/Tracking/{Entity}/{Entity}Persister extends AbstractBaseMysqlPersister implements {Entity}PersisterGateway`.

### [ ] 2.4 Player UseCases per metric
All under `UseCase/Player/Tracking/...`, extending `AbstractLoggedPlayerUseCase`. Validators extend `AbstractLoggedPlayerValidator` (or are standalone for List shapes). Player-edit validators implement `assertPlayerOwns` against the `OwnedByPlayerInterface` virtual hook on each tracking DataModel (where applicable: `HydrationEntry::$player` virtual hook → `$this->summary->player`).

- [ ] **Steps** (`UseCase/Player/Tracking/Steps/`):
  - [ ] `UpsertStepsForDayUseCase` — `(date, count)` → create or update the `StepsDailyEntry` for that day.
  - [ ] `DeleteStepsForDayUseCase` — soft-not-needed, hard delete is fine; daily values are user-correctible.
  - [ ] `ListStepsForRangeUseCase` — read-only listing (used by widget + future stats).
- [ ] **Hydration** (`UseCase/Player/Tracking/Hydration/`):
  - [ ] `GetTodayHydrationUseCase` — returns the day's `HydrationDailySummary` + the list of `HydrationEntry`. Lazy-creates the Summary if missing (snapshots `Player.dailyHydrationTargetMl` into `targetMl`).
  - [ ] `UpdateHydrationDailyTargetUseCase` — overrides `targetMl` for a specific day's Summary (does **not** touch `Player.dailyHydrationTargetMl`).
  - [ ] `UpdatePlayerDailyHydrationTargetUseCase` — updates `Player.dailyHydrationTargetMl` (global default for new days). Editable by the player from a profile section.
  - [ ] `AddHydrationEntryUseCase` — creates a `HydrationEntry`; auto-creates the day's Summary if missing; recomputes the Summary aggregate.
  - [ ] `UpdateHydrationEntryUseCase` — same recompute on the linked Summary.
  - [ ] `DeleteHydrationEntryUseCase` — same recompute.
- [ ] **Sleep** (`UseCase/Player/Tracking/Sleep/`):
  - [ ] `LogSleepUseCase` — `(bedAt, wakeAt, quality?)`. Computes `date = wakeAt::date`. Validator: `wakeAt > bedAt`, quality ∈ `[1,5]` when non-null, no duplicate for `(player, date)`.
  - [ ] `UpdateSleepUseCase` — same rules + ownership.
  - [ ] `DeleteSleepUseCase`.
  - [ ] `ListSleepForRangeUseCase`.
- [ ] **Weight** (`UseCase/Player/Tracking/Weight/`):
  - [ ] `LogWeightUseCase` — `(loggedAt, valueGrams)`. Validator rejects duplicate (`player`, `date(loggedAt)`).
  - [ ] `UpdateWeightUseCase`.
  - [ ] `DeleteWeightUseCase`.
  - [ ] `ListWeightForRangeUseCase` (powers the future progression graph).

### [ ] 2.5 Player REST controllers
Under `Infrastructure/Controller/Player/Tracking/`. Match the `WorkoutPlayerController` style (`POST` for actions, `GET` for reads, `PUT` for updates, `DELETE` for deletes). All routes under `/api/player/tracking/*`, gated by `ROLE_PLAYER`.
- [ ] `StepsPlayerController` — `PUT /api/player/tracking/steps/{date}` (upsert), `DELETE`, `GET /api/player/tracking/steps?from=…&to=…`.
- [ ] `HydrationPlayerController` — `GET /today` (full summary + entries), `PUT /today/target` (override), `PUT /target` (player global), `POST /entries`, `PUT /entries/{id}`, `DELETE /entries/{id}`.
- [ ] `SleepPlayerController` — `POST /api/player/tracking/sleep`, `PUT /{id}`, `DELETE /{id}`, `GET ?from=…&to=…`.
- [ ] `WeightPlayerController` — `POST /api/player/tracking/weight`, `PUT /{id}`, `DELETE /{id}`, `GET ?from=…&to=…`.
- [ ] DataOutputs use the date-string convention (`?->format(\DateTimeInterface::ATOM)`).

### [ ] 2.6 Player frontend: dashboard tracking widget
- [ ] `frontend/website/src/features/tracking/` — new feature folder.
- [ ] `<TrackingWidget />` — composite. Four sub-cards stacked or grouped: `<StepsCard />`, `<HydrationCard />`, `<SleepCard />`, `<WeightCard />`. Each shows the day's value + an inline editor (no modal — cheap to edit on the spot).
- [ ] `<HydrationCard />` shows the progress bar `amountConsumedMl / targetMl` + a "+ Add entry" affordance and a small list of today's entries with Trash icons (reuse `<button class="icon-button --danger">` from the existing icon kit).
- [ ] `<StepsCard />` — single int input (count for today).
- [ ] `<SleepCard />` — `bedAt` + `wakeAt` + `quality` (1–5 emoji selector). Shows last night's record if any.
- [ ] `<WeightCard />` — single int input in kg (UI), converted to grams at the boundary (`* 1000`).
- [ ] Mount the `<TrackingWidget />` on the dashboard (`/`). Layout: above or beside the existing "Next Workout" widget — TBD by the user during implementation; default to a vertical stack.
- [ ] All styling stays theme-agnostic (CSS variables only). Reuse existing `.card`, `.icon-button`, `.status-badge` patterns.

### [ ] 2.7 Migration + schema HTML
- [ ] `php bin/console make:migration` → 5 new tables + 1 ALTER on `player_data_model` (`daily_hydration_target_ml INT NOT NULL DEFAULT 1000`).
- [ ] Apply on dev + test DBs.
- [ ] Regenerate `specifications/database-schema.html`.

### [ ] 2.8 Verification
- [ ] **Unit tests**: every Validator under `tests/Unit/Domain/Validator/Player/Tracking/...`. Cover happy / each rule / accumulation / `assertPlayerOwns` for player-edit shapes.
- [ ] **Integration tests**: every UseCase under `tests/Integration/UseCase/Player/Tracking/...`. Use the manual instantiation pattern (stub `LoggedPlayerResolverInterface`) — see `StartEmptyWorkoutUseCaseTest` for the canonical reference.
- [ ] **Service tests**: `tests/Unit/Domain/Service/Tracking/Hydration/HydrationAggregateEvaluatorTest`, `tests/Unit/Domain/Service/Tracking/Sleep/SleepDurationEvaluatorTest`. Domain only; mock gateways.
- [ ] cURL smoke: log a hydration entry on a fresh day → Summary auto-created with `targetMl=1000`, `amountConsumedMl=value` → override target → log another entry → aggregate updates. Same shape for steps / sleep / weight (duplicate-day rejected as 422).
- [ ] `composer qa` green; `npm run typecheck && lint && build` green on `frontend/website`.

---

## Phase 3 — Leveling sub-domain (entities + admin curve + XP-on-completion + journal + header)

### [ ] 3.1 DataModels + Player columns
- [ ] Add to `PlayerDataModel`: `int $level` (default 1, ≥ 1), `int $currentXp` (default 0, ≥ 0), `int $xpToNextLevel` (default = computed at registration, > 0).
- [ ] `Domain/DTO/DataModel/Leveling/EarnedExperience/EarnedExperienceDataModel` — `player` (M:1), `label` (string), `amount` (INT > 0), `earnedAt` (DATETIME), `sourceType` (string enum: `quest` | `workout`), `sourceId` (string ULID), `isLocked` (bool, default false).
- [ ] `Domain/DTO/DataModel/Leveling/LevelBracket/LevelBracketDataModel` — `fromLevel` (INT ≥ 1), `toLevel` (INT, nullable), `coefficientA` (INT), `exponentK` (INT ≥ 1), `offsetB` (INT). Unique on `fromLevel`. Implicit ordering by `fromLevel`.
- [ ] `Domain/DTO/DataModel/Leveling/LevelingConfig/LevelingConfigDataModel` — singleton (well-known fixed id `LEVELING_CONFIG_ID = '01000000000000000000000000'` documented as a `public const string`). Fields: `xpPerWorkoutMinute` (INT ≥ 50, default 50).
- [ ] `Domain/Registry/Leveling/EarnedExperience/EarnedExperienceSourceTypeRegistry` — `QUEST`, `WORKOUT`.

### [ ] 3.2 Domain service: `LevelingCalculator`
- [ ] `Domain/Service/Leveling/LevelingCalculator` (final, readonly). Constructor: `LevelBracketProviderGateway $gateway`.
- [ ] `marginalCostFor(int $level): int` — finds the bracket containing `$level` (`fromLevel ≤ $level && (toLevel === null || $level ≤ toLevel)`); throws `LogicException` if none matches (config invariant violated). Computes `a × n^k + b`.
- [ ] `applyEarnedAmount(PlayerDataModel $player, int $earned): void` — pure mutation: `currentXp += earned`; while `currentXp ≥ xpToNextLevel`: `currentXp -= xpToNextLevel`; `level++`; `xpToNextLevel = marginalCostFor(level + 1)`. Used by the cron + by Phase 5's same-day workout edit propagation.
- [ ] Unit tests: marginal cost in bracket #1, #2, #3; level-up rolling overflow; multi-level skip when a single earnedAmount ≥ several levels; throws when no bracket covers the level.

### [ ] 3.3 Player baseline at registration
- [ ] Modify `PlayerPersister::create` to set `level=1`, `currentXp=0`, `xpToNextLevel = LevelingCalculator::marginalCostFor(2)`, `dailyHydrationTargetMl=1000` before delegating to `doCreate`. Use the `LevelingCalculator` injected via constructor.
- [ ] Update `RegisterPlayerUseCase` integration test to assert the four baseline columns.

### [ ] 3.4 Gateways + Repositories + Persisters (Leveling entities)
- [ ] `EarnedExperienceProviderGateway` + `EarnedExperiencePersisterGateway` + `EarnedExperienceRepository` + `EarnedExperiencePersister`. Provider methods: `findUnlockedBefore(\DateTimeImmutable $cutoff)`, `findAllByPlayerForJournal(PlayerDataModel, int $page, int $perPage)`, `countByPlayerForJournal(PlayerDataModel)`, `findOneBySourceTypeAndId(string $sourceType, string $sourceId)` (used by Phase 5's same-day edit propagation).
- [ ] `LevelBracketProviderGateway` + `LevelBracketPersisterGateway` + `LevelBracketRepository` + `LevelBracketPersister`. Provider: `findAllOrderedAsc()`, `findContainingLevel(int $level)`, `findOneByIdForAdminAction(string $id)`.
- [ ] `LevelingConfigProviderGateway` + `LevelingConfigPersisterGateway` + repository + persister. Provider: `getSingleton(): LevelingConfigDataModel` (eager-loads the well-known id; throws if missing — Phase 3.10 seed creates it).

### [ ] 3.5 Backend: workout COMPLETED → `EarnedExperience` generation
- [ ] Modify `FinishWorkoutUseCase`: after persisting the workout (status COMPLETED, `dateEnd=now`), if `dateEnd ≥ startOfToday(Europe/Paris)` (i.e. not retroactive — see §Phase 5.6 for the retro guard refinement), compute `duration_minutes = round((dateEnd - dateStart) / 60)` and `amount = duration_minutes × LevelingConfig.xpPerWorkoutMinute`. If `amount > 0`, create an `EarnedExperience` with `sourceType=workout`, `sourceId=workout.id`, `label="Workout: " . workout.name`, `earnedAt = workout.dateEnd`, `isLocked=false`.
- [ ] Wire the new dependencies through `FinishWorkoutUseCase` (inject `LevelingConfigProviderGateway`, `EarnedExperiencePersisterGateway`, `ClockInterface`). Already-wired: `WorkoutPersisterGateway`, `PersonalBestEvaluator`, `PersonalBestPersisterGateway`.
- [ ] **Note**: this step does **not** yet bump `Player.level` / `currentXp` — those are mutated only by the nightly cron. Phase 5's locking story closes that loop.
- [ ] `FinishWorkoutDataOutput` gains `?int $earnedXp` so the player's "workout finished" toast/page can show "+X XP".

### [ ] 3.6 Admin LevelBracket CRUD
- [ ] UseCases under `UseCase/Admin/Leveling/LevelBracket/`:
  - [ ] `CreateLevelBracketUseCase`.
  - [ ] `UpdateLevelBracketUseCase`.
  - [ ] `DeleteLevelBracketUseCase`.
  - [ ] `ListLevelBracketsUseCase`.
  - [ ] `GetLevelBracketDetailsUseCase`.
- [ ] Validators: `CreateLevelBracketValidator`, `UpdateLevelBracketValidator` enforce: `fromLevel ≥ 1`, `exponentK ≥ 1`, contiguity / non-overlap / single-open-ended-last invariants when the new/updated bracket is taken into account against the existing list. Error code: `LEVEL_BRACKET_VALIDATION_FAILED`. Multiple named codes if needed: `OVERLAPPING_BRACKETS_CODE`, `NON_CONTIGUOUS_CODE`, `MULTIPLE_OPEN_ENDED_CODE`, `MISSING_FROM_LEVEL_ONE_CODE`, `NEGATIVE_MARGINAL_COST_CODE`.
- [ ] DataInputs / DataOutputs follow the v0 admin-CRUD shape (`Create…DataInput`, `Update…DataInput`, `Delete…DataInput {deletedId}` etc.).
- [ ] `LevelBracketAdminController` under `Infrastructure/Controller/Admin/Leveling/` with the standard 5 routes under `/api/admin/level-brackets`.
- [ ] Frontend admin: `frontend/admin/src/features/levelBrackets/` with `LevelBracketListPage`, `LevelBracketCreatePage`, `LevelBracketEditPage`. The list page renders the curve preview (small chart of marginal cost per level) so the admin sees the impact of an edit before saving.

### [ ] 3.7 Admin LevelingConfig (`xpPerWorkoutMinute`)
- [ ] `UseCase/Admin/Leveling/LevelingConfig/GetLevelingConfigUseCase` (returns the singleton).
- [ ] `UseCase/Admin/Leveling/LevelingConfig/UpdateLevelingConfigUseCase` — updates `xpPerWorkoutMinute`. Validator: integer, ≥ 50. Error code: `LEVELING_CONFIG_VALIDATION_FAILED`.
- [ ] `LevelingConfigAdminController` under `Infrastructure/Controller/Admin/Leveling/` — `GET /api/admin/leveling-config`, `PUT /api/admin/leveling-config`.
- [ ] Frontend admin: same `frontend/admin/src/features/levelBrackets/` page area gets a small "Global config" form alongside the bracket list.

### [ ] 3.8 Player XP journal
- [ ] `UseCase/Player/Leveling/EarnedExperience/ListEarnedExperienceUseCase` — paginated (`{page, perPage}`, `MAX_PER_PAGE = 50`), ordered by `earnedAt DESC`. Returns `EarnedExperienceJournalDataOutput {items, page, perPage, totalCount}`. Each item exposes `id, label, amount, earnedAt(ISO), sourceType, sourceId, isLocked`.
- [ ] `EarnedExperiencePlayerController::journal` → `GET /api/player/leveling/journal?page=1&perPage=20`.
- [ ] `frontend/website/src/features/leveling/JournalPage.tsx` — paginated list. Each row: date + label + `+X XP` + a small lock icon when `isLocked`. Route `/leveling/journal`, linked from the header dropdown.

### [ ] 3.9 Player frontend: header progress bar
- [ ] Backend: extend `Domain/DTO/DataOutput/Player/Profile/PlayerProfileDataOutput` (or the equivalent already returned by an existing endpoint — verify on disk; if absent, add `GetPlayerProfileUseCase` + `GET /api/player/profile`) with `level`, `currentXp`, `xpToNextLevel`. Used by the header to render the progress bar.
- [ ] Frontend: `<PlayerLevelBadge />` component in `frontend/website/src/layout/`. Renders `Lvl {level} • {currentXp}/{xpToNextLevel}` + a small progress bar. Mount in `<AppLayout />` header (right of the brand, left of the nav dropdown).
- [ ] Reuses CSS variables; styling consistent with the parchment theme.

### [ ] 3.10 Migration + seed + schema HTML
- [ ] Migration: 3 new tables (`earned_experience_data_model`, `level_bracket_data_model`, `leveling_config_data_model`) + ALTER `player_data_model` (3 new INT columns).
- [ ] Backfill existing players: `UPDATE player_data_model SET level=1, current_xp=0, xp_to_next_level=4000` (= `1000 × 2² + 0` from the seeded bracket #1; hard-coded in the migration so it doesn't depend on the still-empty `level_bracket` rows at backfill time — the seed inserts come right after).
- [ ] Seed inserts (in the same migration):
  - 3 `LevelBracket` rows (1–10: `1000×n²+0`, 11–20: `3000×n²+50000`, 21–∞: `500×n³+1000000`).
  - 1 `LevelingConfig` singleton row with `xpPerWorkoutMinute=50` and the well-known id.
- [ ] Regenerate `specifications/database-schema.html`.

### [ ] 3.11 Verification
- [ ] Unit tests on `LevelingCalculator`, every Leveling Validator, and every admin UC integration test.
- [ ] Player UC integration tests cover `ListEarnedExperienceUseCase` (pagination, ordering, cross-player isolation).
- [ ] `FinishWorkoutUseCaseTest` updated: completing a 60-minute workout creates a 3000-XP `EarnedExperience` (`sourceType=workout`, `isLocked=false`); a workout with `dateStart === dateEnd` (0 minutes) creates none.
- [ ] cURL smoke: complete a workout → see the new `EarnedExperience` in `/api/player/leveling/journal`. Player's `level` / `currentXp` are still untouched (cron lands in Phase 5).
- [ ] Admin smoke: edit a bracket boundary → 422 if it breaks contiguity; valid edits succeed. Update `xpPerWorkoutMinute` → reject `< 50` with 422; valid value → 200.
- [ ] `composer qa` green; both frontends green.

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
