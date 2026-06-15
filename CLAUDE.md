# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

The MVP/v0 plan that produced the current codebase has been executed and is archived at `@specifications/v0/dev-plan.md` (paired with `specifications/v0/initial-requirements.md`). **v1 is the active scope going forward** — its plan will live at `specifications/v1/dev-plan.md` (to be generated from `specifications/v1/initial-requirements.md`). When v1's dev-plan exists, it becomes the source of truth for "what's done" and "what's next."

## v0 / MVP state (the codebase you'll inherit when starting v1)

The MVP plan in `specifications/v0/dev-plan.md` was executed phase-by-phase to produce the current codebase. v1 has not started yet; the summary below describes what's already built, not active work scope. Once `specifications/v1/dev-plan.md` exists, **always start a session by reading it** to see which subsections are checked off (`[x]`) and which are next (`[ ]`).

As of the v0 close-out:
- **Phases 0 → 7 are functionally complete** (foundation, entities, admin REST API + frontend, full Player REST API, full Player website with D&D theming + month-grid `/planning` calendar). The ⏸ admin/player checkpoint is passed. Phase 7's manual Chrome/Firefox smoke remains pending — non-blocking.
- **Phase 8 (Hardening) is `[~]`**: 3/4 done.
  - **Coverage baseline OK**: 35/35 concrete UseCases tested, 25/25 non-abstract validators tested, 3/3 stateful Domain services tested. Run: `composer qa` → 241 tests / 507 assertions green.
  - **CI live** at `.github/workflows/ci.yml` — three parallel jobs (`backend` with PHP 8.4 + pcov + MySQL 8.4 service, `frontend-admin`, `frontend-website`). The CI is currently green. Required step that's easy to forget: `php bin/console cache:warmup --env=dev` before `composer qa`, because `phpstan-symfony` reads `var/cache/dev/App_KernelDevDebugContainer.xml` while CI runs `APP_ENV=test`.
  - **`README.md` at repo root** with setup walkthrough + architecture pointer.
  - **Dockerfile prod / `compose.prod.yaml` is `[ ]` — deferred** until the user picks a deployment target (VPS / Fly.io / cloud / etc.).
- **Workout-level chantiers landed during late Phase 7 (after the original 7.8 D&D pass)** — read these before touching anything related to sets / workouts:
  - **Planned vs Achieved separation**: `UpdateExerciseSetPlannedUseCase` is allowed on `PLANNED` only; `UpdateExerciseSetAchievedUseCase` on `IN_PROGRESS` only; `AddExerciseSetUseCase` accepts `planned*` on PLANNED workouts and `achieved*` on IN_PROGRESS workouts (rejected with `STATUS_FIELD_MISMATCH_ERROR_CODE` otherwise). The frontend wires this via a `mode: 'planned' | 'achieved'` prop threaded through `ExerciseEditor` → `AddSetForm` / `ExerciseSetRow` / `SetValuesForm`. `PlannedWorkoutView` (mode='planned') and `LiveWorkoutEditor` (mode='achieved') reuse the same set-editing components.
  - **`isComplete` is auto-derived**: the field on `ExerciseSetDataModel` (renamed from `completed` to follow the new boolean `is`/`has` naming convention) is computed by `Domain/Service/ExerciseSetCompletionEvaluator::isComplete()` whenever the `AddExerciseSet` or `UpdateExerciseSetAchieved` UseCases write achieved values. The old `MarkExerciseSetCompletedUseCase` + its route + tests have been deleted; the frontend "Mark complete" button is gone too.
  - **Workout `name` is auto-derived** at create time by `WorkoutPersister::create` if empty, format `"<Day> <Morning|Afternoon>"` (English day, Morning if hour < 12). Reference precedence: `plannedAt → dateStart → clock->now()`. The migration `Version20260502165231` backfills existing rows.
  - **Workout-level aggregates** (`duration`, `volume`, `distance`, `inclineMeters`) are stored on the workout and recomputed by `Domain/Service/WorkoutAggregateEvaluator::evaluate(WorkoutDataModel): WorkoutDataModel` (mutates in place + returns the same instance). The trigger lives in `WorkoutPersister::update` — guarded by `if (COMPLETED === $model->status)` — so any future "edit a completed workout" UC will keep the aggregates fresh for free.
  - **Calendar feed**: `GET /api/player/workouts/calendar?year=Y&month=M` (use case `ListWorkoutsByMonthUseCase`). The DQL gateway uses a 3-branch OR mimicking the precedence `dateEnd → dateStart → plannedAt` because Doctrine 3 refuses `COALESCE` in WHERE/ORDER BY. The `<MonthCalendar<TEvent>>` React component is **deliberately generic** and reusable for non-workout calendars later.
  - **Naming convention for booleans**: prefix `is` (state) or `has` (possession) — added to `specifications/conventions.md`. 3rd-person-singular verbs (`tracksWeight`) are accepted when more idiomatic.
- **Frontend icon-button kit**: a small set of inline SVG icons at `frontend/website/src/components/icons/index.tsx` (Bell, Gear, Logout, Trash, Check, Pencil, XMark, Save). They're consumed by `<button class="icon-button">` (no border, no chrome) plus modifiers `--danger` (trash, red) / `--success` (check, green). New icon-only actions across the player site **must reuse this pattern** — do not introduce `lucide-react` or a similar dep.
- **Reusable badge**: `frontend/website/src/components/WorkoutStatusBadge.tsx` is the single source of truth for the workout-status pill (label + colour). The list rows and the workout details header both consume it. CSS modifiers `.status-badge--{planned,in_progress,completed,canceled}`.

`composer qa` last known green: cs ✅, stan ✅, phpunit ✅ (241 tests / 507 assertions). `npm run typecheck && lint && build` green on `frontend/website`. CI green on the latest push.

When picking up work, **never rebuild what's already in place** — always check the on-disk reality first (`composer.json`, `src/`, `config/packages/`, `frontend/website/src/`, plus the v0 dev-plan checkboxes for what was already covered) before scaffolding. The v0 dev-plan's "Decisions / deviations" section is the authoritative summary of conventions that apply to all subsequent work — including v1 (esp. the Admin/Player abstract split, the date-as-ISO-8601-string DataOutput convention, the controller-per-batch landing strategy, the moved registration endpoint, the planned/achieved split, and the auto-derived `isComplete` + `name` + workout aggregates).

## Working mode

Implementation work proceeds **step-by-step**, where each "step" is one numbered subsection of the active dev-plan — going forward, `specifications/v1/dev-plan.md` (same numbering convention as the archived v0 plan: `0.1`, `0.2`, …, `1.1`, `1.2`, …). After completing a step:

1. Run `composer qa` (or the relevant subset) to confirm green.
2. Update `specifications/v1/dev-plan.md` — flip `[ ]` to `[x]` for everything genuinely done in that step. Keep `[~]` for partially-done steps where some leaf bullets remain pending in a later sub-step.
3. **Pause** and summarize what was done and what design choices were made (especially anything that deviates from `conventions.md` — flag those clearly so the user can roll back).
4. Wait for the user to say "next" / "go" / similar before starting the next step.

Do not commit, push, or chain multiple steps without explicit user confirmation. If you encounter a decision that materially deviates from `conventions.md` or the dev-plan, raise it for review before applying — don't silently take the deviation. When you do take one, **append a note to `specifications/v1/dev-plan.md`'s "Decisions / deviations" block** so the next session inherits the working contract.

The user works in French; responses can be in French.

## Authoritative specifications

The `specifications/` folder holds the project's source of truth. v0 (MVP) is archived; v1 is the active scope (initial-requirements forthcoming, dev-plan to be generated from it). Read them before designing or implementing anything:

- **`specifications/conventions.md`** — non-negotiable coding rules (final classes, `declare(strict_types=1)`, Yoda conditions, class suffixes, Domain isolation, DTO categories, Repository/Persister + Gateway pattern, UseCase contract). Apply these to every PHP file you write. **Version-agnostic** — applies to v0, v1, and beyond.
- **`specifications/v1/initial-requirements.md`** — v1 product scope (forthcoming — to be added by the user before generating the v1 dev-plan).
- **`specifications/v1/dev-plan.md`** — the v1 executable roadmap with `[x]` / `[ ]` / `[~]` checkboxes per subsection and per leaf bullet. Source of truth for "what's done" and "what's next." To be generated from `specifications/v1/initial-requirements.md`.
- **`specifications/v0/initial-requirements.md`** & **`specifications/v0/dev-plan.md`** — archived MVP scope and roadmap. The current codebase is the result of executing the v0 plan; read them for context on what's already built and the conventions that emerged (esp. the v0 dev-plan's "Decisions / deviations" block).

Per `conventions.md`, whenever the database schema changes you must regenerate `specifications/database-schema.html` (HTML5 schema diagram).

## Architecture (current shape on disk)

The conventions impose a strict **Domain / Infrastructure / UseCase** split:

- **`Domain/`** — pure business code. Cannot import anything from outside `Domain` *except* the five documented exceptions: `Doctrine\DBAL\Types\Types` and `Doctrine\ORM\Mapping as ORM` only inside `Domain/DTO/DataModel/{SubDomain}`; `Doctrine\Common\Collections\{Collection, ArrayCollection}` only inside `Domain/DTO/DataModel/{SubDomain}` (Doctrine forces `Collection<…>` typing on to-many relations); `Symfony\Component\Security\…` only inside `UserDataModel`; `\Exception` only inside `Domain/Exception`.
  - `Domain/DTO/DataInput|DataOutput|DataModel/` — three DTO flavors, public properties, no getters/setters, each implements its DTO interface. `DataModel` = Doctrine entity (suffix `DataModel`, requires `createdAt`/`updatedAt` handled by the persister).
  - `Domain/Gateway/Provider/{SubDomain}/{Entity}/` and `Domain/Gateway/Persister/{SubDomain}/{Entity}/` — interfaces injected in place of concrete repositories/persisters (1-to-1 mapping, no services.yaml wiring needed). Naming: `WorkoutProviderGateway`, `WorkoutPersisterGateway`. **The original convention said "flat under root" — but the code on disk uses `{SubDomain}/{Entity}/` sub-folders (e.g. `Training/Workout/WorkoutPersisterGateway.php`). Match the on-disk layout when adding new ones.**
  - `Domain/Registry/{SubDomain}/{Entity}/` — interfaces holding constants tied to a DTO (e.g. `Training/Workout/WorkoutStatusRegistry`).
  - `Domain/Security/` — `LoggedUserResolverInterface`, `LoggedPlayerResolverInterface`. Implemented in `Infrastructure/Security/`.
  - `Domain/Validator/` — `DomainValidatorInterface`, `AbstractLoggedAdminValidator` (exposes `getLoggedAdmin(): UserDataModel`), `AbstractLoggedPlayerValidator` (exposes `getLoggedPlayer(): PlayerDataModel`).
  - `Domain/DataTransformer/StringDataTransformerInterface` — `slugify()` is contract'd in Domain so validators can use it without touching Infrastructure.
- **`Infrastructure/`** — adapters.
  - `Infrastructure/Repository/{SubDomain}/{Entity}/` — implements a `Provider` gateway. **Never** call generic Doctrine finders (`find`, `findOneBy`, …); write context-named methods like `findOneForWorkoutDetails` or `findOneByIdForPlayerAction`. Never rely on lazy-loading.
  - `Infrastructure/Persister/{SubDomain}/{Entity}/` — extends `AbstractBaseMysqlPersister`, implements a `Persister` gateway, owns `createdAt`/`updatedAt` via `ClockInterface`, and is where post-create/update/delete side effects live (including derived-property computation: `slug` from `label`, hashed `password` from `plainPassword`, etc.).
  - `Infrastructure/DataFixtures/` — Symfony FixtureBundle fixtures (the muscle list in `specifications/v0/initial-requirements.md` is a fixture seed). Fixtures **must inject the matching `*PersisterGateway` and call `create(...)`** — they never set timestamps or call `EntityManager::persist/flush` directly.
  - `Infrastructure/Controller/{Admin,Player,Security,User}/...` — thin HTTP entry points. Together with Commands they are the only callers allowed to reach into `UseCase`. Controllers land **per phase batch** alongside the use cases they expose (not all in one final pass) — the dev-plan's Phase 6.5 is therefore tracked as `[~]` while sub-phases populate it.
  - `Infrastructure/Controller/DomainExceptionListener` — `#[AsEventListener]` on `ExceptionEvent`; maps `ValidationException` → 422, `EntityNotFoundException` → 404, `UnauthorizedException` → 401.
- **`UseCase/`** — `final` classes implementing `UseCaseInterface`, single `execute(DataInputInterface): DataOutputInterface|list<DataOutputInterface>`. Three abstract bases:
  - `AbstractPublicUseCase` — injects `DomainValidatorInterface` (no auth resolution).
  - `AbstractLoggedAdminUseCase` — injects `AbstractLoggedAdminValidator` (use cases under `UseCase/Admin/...`).
  - `AbstractLoggedPlayerUseCase` — injects `AbstractLoggedPlayerValidator` (use cases under `UseCase/Player/...`).
  Only Controllers and Commands may reference `UseCase`.

**Class-name suffix rules** (from `conventions.md`): `DataModel`, `Repository`, `UseCase`, `Validator`. All classes are `final` by default (DataModels excepted, abstracts excepted) and `readonly` when feasible.

### Persister variance pattern (gotcha — keep)
`AbstractBaseMysqlPersister` exposes only **protected** helpers `doCreate` / `doUpdate` / `doDelete` (typed `DataModelInterface`). Each concrete persister implements its own **public** `create`/`update`/`delete` typed per `DataModel` (matching the gateway interface) and delegates to those helpers. PHP's variance rules forbid both narrowing the parent's `public create(DataModelInterface)` parameter to `create(MuscleDataModel)` (parameter contravariance) and inheriting the wider return when the gateway requires the narrow type (return covariance) — the protected-helper pattern sidesteps both.

### Date serialization in DataOutput
`JsonResponse` calls `json_encode` directly (no Symfony Serializer in the path), which dumps `\DateTimeImmutable` as `{date, timezone_type, timezone}`. Convention: **DataOutput classes that carry date fields type them as `?string` and the use case formats with `?->format(\DateTimeInterface::ATOM)` at the DTO boundary** (RFC 3339 / ISO 8601). Apply this pattern to every player-facing DataOutput.

### Theming note
The player website is D&D-flavored / medieval-fantasy — colors **must** be CSS variables (per requirements + dev-plan §7). The admin (Phase 5) uses antd's light/dark algorithms via `ConfigProvider` and persists the choice to `localStorage`.

### Quest auto-progression hooks (Phase 4.4 — keep this checklist in sync)
Every write that changes a quest-measurable metric must call `QuestProgressionEvaluator::refreshFor($player, <metric>, $this->clock->now())` **after** the write succeeds, so automatic quests recompute and flip to `CLAIMABLE`. The evaluator + a `ClockInterface` are constructor-injected into each UseCase below (this is why their integration tests build them with two extra args from the container). When you add a new tracking metric or a new write path, wire the hook here too — and add a matching `QuestMetricRegistry` value + `MetricResolver`.

| Metric (`QuestMetricRegistry`) | UseCases that must call `refreshFor` |
| --- | --- |
| `STEPS_DAILY` | `UpsertStepsForDayUseCase`, `DeleteStepsForDayUseCase` |
| `HYDRATION_ML_DAILY` | `AddHydrationEntryUseCase`, `UpdateHydrationEntryUseCase`, `DeleteHydrationEntryUseCase` |
| `SLEEP_DURATION_MINUTES` | `LogSleepUseCase`, `UpdateSleepUseCase`, `DeleteSleepUseCase` |
| `WORKOUT_COUNT` + `WORKOUT_DURATION_MINUTES` | `FinishWorkoutUseCase` (calls `refreshFor` once per metric) |

Weight has **no** quest metric → its UseCases are intentionally not hooked. Target-only writes (`Update*DailyTarget*`, `UpdatePlayer*Target`) and pure reads (`GetToday*`) don't change a measured metric → no hook. The metric value is recomputed for the **current** period (Europe/Paris) via `clock->now()`, so backfilling a past day does not retro-complete today's quest (and vice-versa).

## Commands

PHP / Symfony:

```bash
composer install                  # install deps; auto-runs cache:clear and assets:install
php bin/console                   # list all Symfony console commands
php bin/console cache:clear
symfony server:start -d           # start the local web server in the background (use `symfony serve` for foreground)
symfony server:stop
```

Local dev server: Symfony CLI (`symfony` binary) on https://127.0.0.1:8000. Not the built-in PHP server.

Frontend admin (React + Vite, runs in a `frontend-admin` container, network_mode host):

```bash
docker compose up -d frontend-admin
docker compose exec -T frontend-admin sh -c "npm run typecheck"
docker compose exec -T frontend-admin sh -c "npm run lint"
docker compose exec -T frontend-admin sh -c "npm run build"
# Dev server: http://localhost:5173
```

Quality toolchain (run before committing — all three are also enforced by a **pre-commit git hook**, so a commit that fails any of them will be rejected):

```bash
composer qa                                 # cs + stan + test (full)
composer test                               # full PHPUnit suite
composer test:unit                          # unit suite only (what the pre-commit hook runs)
composer test:integration                   # integration suite (needs MySQL up)
composer stan                               # phpstan analyse
composer cs                                 # php-cs-fixer dry-run
composer cs:fix                             # php-cs-fixer auto-fix

vendor/bin/phpunit --filter SomeTest        # run a single test class or method
```

Never bypass the hook with `--no-verify`; if it fails, fix the underlying issue (missing strict_types declaration, non-Yoda condition, type error, failing test, …) before re-committing.

## Tests pattern

- **Every UseCase has an integration test** at `tests/Integration/UseCase/<same-relative-subpath>/<UseCaseName>Test.php`. Extends `KernelTestCase`. Happy path + every validation/not-found/unauthorized branch. Use try/catch on `ValidationException` to inspect `violations` + `errorCode` (not `expectException()` alone — too coarse).
- **Every Validator has a unit test** at `tests/Unit/Domain/Validator/<same-relative-subpath>/<ValidatorName>Test.php`. Mocks gateways with `$this->createMock(...)`. One method per rule. One method that verifies accumulation. One method for the wrong-input-type `\LogicException` guard. Annotate with `#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]` when only stubbing.
- **Player integration tests use manual instantiation of the use case** (not `$container->get(UseCase::class)`). Reason: the use case needs a stubbed `LoggedPlayerResolverInterface` that returns the test's freshly-persisted Player. Pull `EntityManagerInterface` (id `'doctrine.orm.entity_manager'`) and `ManagerRegistry` from the container (both public Doctrine bindings), instantiate `WorkoutPersister` / `WorkoutRepository` directly, and pass the stub resolver. The existing `tests/Integration/UseCase/Player/Training/Workout/StartEmptyWorkoutUseCaseTest.php` is the canonical reference.

## Environment

`.env` ships with `APP_ENV=dev`, `APP_SHARE_DIR=var/share`, and `DEFAULT_URI=http://localhost`. `.env.dev` sets a dev `APP_SECRET`. Local overrides go in `.env.local` (gitignored). PHP 8.4 is required (`composer.json`), and Symfony is pinned to `8.0.*` via the `extra.symfony.require` constraint — keep new Symfony packages on the same minor.

Seeded credentials (from `Infrastructure/DataFixtures/User/`): `admin@akhilleus.test` / `AdminAdmin1!` (ROLE_ADMIN), `player@akhilleus.test` / `PlayerHero1!` (ROLE_PLAYER, has linked `PlayerDataModel` "Player Hero").
