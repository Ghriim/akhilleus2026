# Akhilleus 2026 — Development Plan

## Resume pointer (last session snapshot)

- **Last completed step**: Phase 6.6 — full-lifecycle integration test (`PlayerWorkoutLifecycleTest`, 29 assertions) + cURL smoke flow over all 13 Phase-6 endpoints (auth/lifecycle/finish/read), all green. `composer qa` green (209 tests / 440 assertions). Schema in sync (mapping + DB). **Phase 6 is fully closed** — the player REST API is shippable.
- **Next pending step**: ⏸ checkpoint, then Phase 7 (player frontend — React TS website with D&D theming, JWT-aware fetch wrapper, dashboard / live workout / history / achievements pages).
- The "Decisions / deviations" block below + each phase's inline notes are the working contract — read them before designing anything new.
- `specifications/initial-requirements.md` is the **frozen user spec** and must not be edited. All clarifications/decisions go into this dev-plan and `specifications/conventions.md`.

## Context

`specifications/initial-requirements.md` describes a gamified, RPG-styled training-tracking app built with Symfony 8 / PHP 8.4, MySQL (Docker), a JWT REST API, a React Admin (TS) admin, and a React TS player website. `specifications/conventions.md` imposes a strict Domain / Infrastructure / UseCase split with hard isolation rules. The current repo is a bare Symfony 8 skeleton — only `src/Kernel.php` and an empty `src/Controller/` folder exist; no Doctrine, no auth, no quality toolchain.

This plan delivers the requirements end-to-end in the order requested:

1. **Foundation** (toolchain, Docker, abstractions)
2. **Entities** (every DataModel for the full app — done up-front so future phases never re-shape the schema)
3. **Admin path**: everything needed to manage Equipment / Muscle / Movement from the React Admin UI (infra → auth → REST → admin frontend)
4. **Player path**: REST API + React TS website for the player flows
5. **Hardening**

A `⏸ checkpoint` marks the end of the admin path — work pauses there to start the player path as a separate stage.

## Decisions baked in

- **Coach scope**: deferred entirely. The on-disk DataModels for users are `UserDataModel` + `PlayerDataModel` + `AdminDataModel` (see "AdminDataModel introduced" deviation below). Coach is still deferred.
- **IDs**: ULID via `symfony/uid`. Modeled as plain `string` inside `src/Domain/` (so Domain stays free of Symfony imports beyond the four `conventions.md` exceptions). UseCases / fixtures / persisters generate ids via `(string) new Ulid()` at the edges.
- **Pre-commit**: `captainhook/captainhook` runs PHPUnit + PHPStan + PHP-CS-Fixer on every commit.
- **Controllers**: hand-rolled (not API Platform) — the strict UseCase contract is the routing layer, not state-providers.
- **Per CLAUDE.md restructure**: `src/UseCase/` is top-level; controllers live under `src/Infrastructure/Controller/`.

## Open assumptions (flag if you disagree)

- One Symfony repo holds the API; the two React apps live in sibling folders `frontend/admin/` and `frontend/website/` (Vite, separate package.json each), served independently in dev. Switch to a monorepo / separate repos if preferred.
- A "Workout cancellation" status is not in the spec but seems likely needed (e.g. abandoned in-progress workouts). Including `CANCELED` in the status registry; trivial to drop later.
- Personal-best "speed" is `distance / duration` (not `duration * distance` as the spec text says — that would equal volume). Will confirm before implementing M6.

## Decisions / deviations from `conventions.md` and the original plan

Every item below was decided during implementation and is **not** explicitly covered (or is a measured deviation) from `specifications/conventions.md`. A future session can rely on these as the working contract; if the user wants to roll any of them back, the line that introduced it is called out so the change is contained.

### Architecture / convention
- **`AdminDataModel` introduced** (User/AdminDataModel.php) alongside `UserDataModel` + `PlayerDataModel`. Mirrors the `PlayerDataModel` pattern: 1:1 with `UserDataModel`, holds admin-specific profile fields (`firstName`, `lastName`, `jobTitle`, `hiredAt`). Created via `AdminPersister::create(RegisterAdminDataInput)`. Follows the "user is created via a higher-level persister" rule (the `UserPersister` is called inside `AdminPersister::create`). The `AdminFixtures` seeds `admin@akhilleus.test` through this path. Originally the plan said "Coach scope deferred, only User+Player" — the Admin profile entity was added during the admin onboarding work to remove the special case in fixtures and to mirror Player's structure. Coach remains deferred.
- **Admin/Player abstract split for logged-user UseCases.** The original `AbstractLoggedUserUseCase` + `AbstractLoggedUserValidator` were **removed** in Phase 6.1 and replaced by two parallel pairs: `AbstractLoggedAdminUseCase` + `AbstractLoggedAdminValidator` (exposes `getLoggedAdmin(): UserDataModel`, used by all `UseCase/Admin/...`) and `AbstractLoggedPlayerUseCase` + `AbstractLoggedPlayerValidator` (exposes `getLoggedPlayer(): PlayerDataModel`, used by `UseCase/Player/...`). The Player resolver (`Domain/Security/LoggedPlayerResolverInterface` + `Infrastructure/Security/LoggedPlayerResolver`) composes the existing User resolver and `PlayerProviderGateway` and throws `UnauthorizedException` if the authenticated user has no player profile. **Conventions.md was updated to reflect this.** Future roles (Coach…) follow the same pattern.
- **Gateway file layout deviates from the original "flat under root" rule** stated earlier in this same block. On-disk layout is `Domain/Gateway/{Provider,Persister}/{SubDomain}/{Entity}/{Entity}{Provider|Persister}Gateway.php` (e.g. `Domain/Gateway/Persister/Training/Workout/WorkoutPersisterGateway.php`, `Domain/Gateway/Provider/User/PlayerProviderGateway.php`). New gateways must follow the on-disk layout, not the original "flat" sentence.
- **Domain stays free of Symfony imports.** `conventions.md` lists five allowed-in-Domain external namespaces (`Doctrine\DBAL\Types\Types`, `Doctrine\ORM\Mapping`, `Doctrine\Common\Collections\{Collection, ArrayCollection}`, `Symfony\Component\Security` only inside `UserDataModel`, `\Exception` only in `Domain/Exception`). When the question came up of allowing `Symfony\Component\Uid\Ulid` for ID typing, we chose to **keep the rule strict and model ids as plain `string` in Domain** — `DataModelInterface` declares `public string $id { get; set; }`. ULIDs are generated at the edges (UseCases, fixtures, persisters via `(string) new Ulid()`).
- **`Doctrine\Common\Collections\Collection` + `ArrayCollection`** are imported in `MovementDataModel` for its two M:N relations (`secondaryMuscles`, `equipments`). Formalized as the 5th allowed-in-Domain exception in `conventions.md` (scope: `Domain/DTO/DataModel/{SubDomain}` only) — Doctrine ORM forces `Collection<…>` typing on to-many associations and offers no array-based fallback, so this is the same flavor of exception as `Doctrine\ORM\Mapping` and `Doctrine\DBAL\Types\Types`.
- **All inverse 1:M sides are intentionally skipped in Phase 1** (no `Workout->exercises`, no `Exercise->exerciseSets`, no `User->player`). The DB schema is determined entirely by FK columns on the owning side, so deferring the inverse sides costs nothing now and avoids additional `Collection` uses. They get added per UseCase need (likely Phase 6's `FinishWorkoutUseCase` for `Workout->exercises`).
- **DataModel classes are not `final`.** `conventions.md` says "final by default *except DataModels*"; abstract base classes (`DomainException`, `AbstractBaseMysqlPersister`, `AbstractPublicUseCase`, `AbstractLoggedUserUseCase`, `AbstractLoggedUserValidator`) are also non-final since subclasses extend them. Concrete classes everywhere else are `final`.
- **`UserDataModel` has 4 getter methods** (`getRoles`, `getPassword`, `getUserIdentifier`, `eraseCredentials`) — required by `UserInterface` / `PasswordAuthenticatedUserInterface`. This is the single exception to "DTOs have public properties and no getters/setters". `email` is annotated `@var non-empty-string` so PHPStan accepts the `getUserIdentifier(): non-empty-string` return.
- **Gateway interfaces are scaffolded phase by phase, not all up-front.** Phase 1.3 ships only the 3 admin gateway pairs (Muscle / Equipment / Movement); the other 6 are added when their phase begins (User/Player in Phase 3, Workout/Exercise/ExerciseSet/PersonalBest in Phase 6). Rationale: interfaces with zero known consumers are dead code that biases the design.
- **Gateway file layout is flat** under `Domain/Gateway/Provider/` and `Domain/Gateway/Persister/` (one file per entity per role). No per-entity sub-folders, despite the Registry layout using them — gateways are 1 file per entity per role, sub-folders would be needless ceremony.
- **Persisters return the managed entity from `create` and `update`** (typed narrowly per entity in the gateway interface; `delete` stays `void`). `AbstractBaseMysqlPersister` exposes only **protected** helpers `doCreate` / `doUpdate` / `doDelete` (typed `DataModelInterface`); each concrete persister implements its own `public create/update/delete` typed per `DataModel` (matching the gateway) and delegates to those helpers. This is required by PHP's variance rules: parameter contravariance forbids narrowing a parent's `public create(DataModelInterface)` to `public create(MuscleDataModel)` in a child, and return covariance forbids inheriting the wider `DataModelInterface` return when the gateway requires `MuscleDataModel`. The protected-helper pattern sidesteps both at once.

### Persistence / column conventions
- **Decimal columns use `?string` + `@var numeric-string` PHPDoc.** Doctrine returns `NUMERIC` columns as PHP `string` to preserve precision. The chosen scales:
  - weight: `NUMERIC(6,2)` — kg, up to 9999.99
  - duration: `INT` — seconds
  - distance: `NUMERIC(10,2)` — meters, up to 99,999,999.99 (covers ultra-distance)
  - incline percent: `NUMERIC(5,2)` — up to 999.99 %
  - incline meters: `NUMERIC(8,2)` — up to 999,999.99 m
  - personal_best.value: `NUMERIC(15,4)` — wide enough for any of the seven categories
- **M:N join columns are explicitly named** via `#[ORM\JoinColumn(name: 'movement_id', …)]` + `#[ORM\InverseJoinColumn(name: 'muscle_id'/'equipment_id', …)]` on `MovementDataModel` — Doctrine's auto-generated names would have been `movement_data_model_id` / `muscle_data_model_id`, which are noisy.
- **All FK relations have explicit `#[ORM\JoinColumn(nullable: false)]`** unless the relation is genuinely optional (`PersonalBestDataModel.workout` and `.exerciseSet` are nullable provenance pointers).
- **`PersonalBestDataModel`** has a unique constraint on `(player_id, movement_id, type)` so the upsert-on-improvement pattern in Phase 6's evaluator is safe at the DB level.

### Tooling / build
- **`phpunit.xml.dist` was renamed `phpunit.dist.xml`** (current Symfony Flex / PHPUnit 13 convention). Functionally equivalent.
- **PHPUnit's empty-suite exits with code 1.** To keep the captainhook pre-commit hook from failing while there are no real tests yet, `tests/Unit/SmokeTest.php` exists as a single-assertion anchor that verifies the `App\` autoloader. It will be replaced by real per-UseCase tests in Phase 4+ and can be deleted once any other Unit test exists.
- **Pre-commit hook runs Unit suite only.** Integration tests need a live DB and would block every commit if MySQL isn't running. Integration tests run via `composer test:integration` / CI instead. Composer scripts: `cs`, `cs:fix`, `stan`, `test`, `test:unit`, `test:integration`, `qa` (= `cs` + `stan` + `test`).
- **`final_class` PHP-CS-Fixer rule is intentionally NOT enabled** — it would auto-finalize DataModels, which `conventions.md` explicitly excludes. Concrete classes are made `final` manually.
- **`nelmio/api-doc-bundle` is installed but not registered in `config/bundles.php`.** Its Flex recipe was IGNORED because `composer.json` has `extra.symfony.allow-contrib: false`. We'll register it manually when Phase 4/5 actually exposes the OpenAPI route.
- **Doctrine fixtures bundle is in `require-dev`** (not `require` as the original plan had it) — fixtures are not a runtime concern.

### Validator / UseCase norm refactor (Phase 6.2)
A cross-cutting refactor was applied during 6.2 to set the **new norm** for validators and use cases across the entire project. Existing phases were migrated in-place. The norm is now formalised in `specifications/conventions.md` (sections "Conventions for UseCases" and "Conventions for Validators"); the changes below summarise what moved and why.

- **`DomainValidatorInterface` removed.** Validator signatures are now heterogeneous (typed parameters per use case shape), so a shared `validate(DataInputInterface $input): void` contract is meaningless. Validators are standalone classes; the use case calls its concrete validator directly via a typed property.
- **Abstract use-case bases lost their constructor-injected validator slot.** `AbstractPublicUseCase`, `AbstractLoggedAdminUseCase`, `AbstractLoggedPlayerUseCase` are now empty marker classes (`abstract class implements UseCaseInterface`). Each concrete UseCase declares its own constructor with the deps it needs (validator + gateways + clock + resolver as applicable). No `parent::__construct(...)` call.
- **Use cases dropped the runtime `instanceof + LogicException` guard.** They now declare `public function execute(DataInputInterface $input): YDataOutput` (parent-compatible) plus `/** @param XDataInput $input */` PHPDoc for PHPStan narrowing. Wrong type at runtime → PHP `TypeError` from the validator's typed signature on the very next call (good dev signal, not worth a redundant guard).
- **Validator typed signatures (three shapes).** Create / List / Register: `validate(XDataInput $input)`. Player edit: `validate(PlayerDataModel $player, XDataInput $input, EntityDataModel $entity)`. Admin edit: `validate(XDataInput $input, EntityDataModel $entity)` (no ownership, but entity available for self-match on uniqueness).
- **Player edit flow centralises ownership + state + input rules in the validator.** The use case loads via `findOneByIdForPlayerAction`, checks for null (404), then calls `validate(player, input, entity)`. The validator calls `$this->assertPlayerOwns($player, $entity)` first as defence-in-depth (the gateway already filtered, but the helper guards against future gateway methods that don't), then state checks, then accumulated input violations. **The 404 stays in the use case** (data layer concern) so the validator takes a non-null entity — this avoids a `@phpstan-assert !null` annotation that would trigger PHPStan's `method.alreadyNarrowedType` at every test call site.
- **`Domain/DTO/DataModel/OwnedByPlayerInterface`** introduced. `WorkoutDataModel` and `PersonalBestDataModel` (when added) implement it directly via their `public PlayerDataModel $player` property. `ExerciseDataModel` and `ExerciseSetDataModel` implement it via PHP 8.4 virtual property hooks (`public PlayerDataModel $player { get => $this->workout->player; }` and `=> $this->exercise->workout->player`). Doctrine ignores virtual hooks (no `#[ORM\Column]` attribute).
- **`AbstractLoggedPlayerValidator::assertPlayerOwns(PlayerDataModel, OwnedByPlayerInterface)`** — replaces the original `validateEdit($player, DataModelInterface $modelToEdit, $input)`. Renamed for clarity (the helper is purely an ownership assertion, not a "validate edit"); the unused `$input` parameter was dropped; the `DataModelInterface` type was tightened to `OwnedByPlayerInterface` so `$model->player` is statically known and the original `isset(...)` check became unnecessary (it also concealed a typo `$modeToEdit` that silently disabled the check entirely).
- **Empty validators were dropped.** Admin Delete and GetDetails no longer have a validator (no rules to enforce; the use case is a thin load → 404 → act). 6 validator classes + their tests removed.
- **Status constants moved to the registry.** `WorkoutStatusRegistry::EDITABLE_STATUSES = [PLANNED, IN_PROGRESS]` and `WorkoutStatusRegistry::CANCELLABLE_STATUSES` (same values today) — used by every workout-edit use case (Cancel, Add/Remove/Reorder Movement, ExerciseSet add/update/remove). Per-use-case private const arrays were eliminated.
- **Error code constants moved from use cases to validators.** Idiom is now `XValidator::ERROR_CODE` (single rule), or named (`ILLEGAL_STATUS_CODE`, `FAILED_ERROR_CODE`, `TRACKING_MISMATCH_ERROR_CODE`) when one validator throws several distinct codes. Integration tests reference the validator constant; controllers don't reference any of these constants.
- **Validator unit tests dropped the `testItThrowsLogicExceptionForWrongInputType` method** (typed signature replaces it). Player-edit validator tests gain a `testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer` covering `assertPlayerOwns`.
- **PHP 8.4 readonly + reflection.** Validator unit tests that need to inject an out-of-range `numeric-string|null` value to exercise the validator's defensive regex (e.g. `'fifty'` for `plannedWeight`) must use `(new \ReflectionClass(...))->newInstanceWithoutConstructor()` and set every property by reflection — `setValue` after a constructed readonly is rejected by PHP 8.4. The 2 tests in `AddExerciseSetValidatorTest` and `UpdateExerciseSetAchievedValidatorTest` use this pattern.
- **`MovementProviderGateway::findOneByIdForExerciseAttachment(string $id): ?MovementDataModel`** — context-named lookup added for `AddMovementToWorkoutUseCase`. No eager fetch needed (just the movement + its tracking flags).

### Tracking
- The dev-plan uses `[x]` / `[ ]` checkboxes on every subsection header and every leaf bullet. Tick items off as soon as a step finishes; this is the source of truth for "what's done."

---

## Phase 0 — Foundation

### [x] 0.1 Docker dev environment
- [x] `compose.yaml` services: `php` (custom Dockerfile, PHP 8.4 + composer + xdebug), `database` (mysql:8.4), `mailer` (mailhog) optional.
- [x] `docker/php/Dockerfile`: PHP 8.4 FPM-CLI, composer, opcache, intl/pdo_mysql/zip extensions, symfony-cli binary baked in.
- [x] Bind mount the repo into the php container; expose port 8000 via `symfony serve` from inside or outside.
- [x] `.env` additions: `DATABASE_URL=mysql://app:!ChangeMe!@database:3306/akhilleus?serverVersion=8.4`, `JWT_PASSPHRASE`, `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `CORS_ALLOW_ORIGIN`.

### [x] 0.2 Composer dependencies (production)
- [x] `doctrine/orm`, `doctrine/doctrine-bundle`, `doctrine/doctrine-migrations-bundle`
- [x] `doctrine/doctrine-fixtures-bundle` (the "FixtureBundle" referenced in conventions) — installed as dev-only since fixtures are not a runtime concern.
- [x] `lexik/jwt-authentication-bundle`
- [x] `symfony/security-bundle`, `symfony/serializer-pack`, `symfony/validator`
- [x] `symfony/uid` (ULIDs)
- [x] `symfony/clock` (PSR-20 `ClockInterface` for AbstractBaseMysqlPersister)
- [x] `nelmio/cors-bundle`
- [x] `nelmio/api-doc-bundle` (OpenAPI for the React frontends) — package installed; bundle registration deferred to Phase 4/5 (recipe was IGNORED due to `allow-contrib: false`).

### [x] 0.3 Composer dependencies (dev)
- [x] `phpunit/phpunit`, `symfony/test-pack`, `dama/doctrine-test-bundle` (transactional tests)
- [x] `phpstan/phpstan`, `phpstan/phpstan-symfony`, `phpstan/phpstan-doctrine`, `phpstan/phpstan-phpunit`
- [x] `friendsofphp/php-cs-fixer`
- [x] `captainhook/captainhook`, `captainhook/plugin-composer`

### [x] 0.4 Quality toolchain config
- [x] `phpunit.dist.xml` — bootstrap, test suites for `tests/Unit` and `tests/Integration`.
- [x] `phpstan.dist.neon` — level 8, scan `src/` and `tests/`, Symfony + Doctrine + PHPUnit extensions, baseline empty.
- [x] `.php-cs-fixer.dist.php` — PSR-12 base + custom rules: `declare_strict_types`, `yoda_style` (all comparisons), `ordered_imports`, `no_unused_imports`. Skipped `final_class` rule because it would auto-finalize DataModels (which the convention explicitly excludes).
- [x] `captainhook.json` — `pre-commit` runs in order: `php-cs-fixer fix --dry-run --diff` → `phpstan analyse --memory-limit=1G` → `phpunit --testsuite=Unit`. Integration suite excluded from the hook (DB-dependent); runs via `composer test:integration` / CI. `commit-msg` conventional-commit gate left disabled.
- [x] Composer scripts: `composer test`, `composer test:unit`, `composer test:integration`, `composer stan`, `composer cs`, `composer cs:fix`, `composer qa` (cs + stan + test).

### [x] 0.5 Source skeleton & abstractions
Create directory layout and base classes:

```
src/
├── Domain/
│   ├── DTO/
│   │   ├── DataInput/DataInputInterface.php
│   │   ├── DataOutput/DataOutputInterface.php
│   │   └── DataModel/DataModelInterface.php
│   ├── Exception/
│   │   ├── DomainException.php
│   │   ├── EntityNotFoundException.php
│   │   ├── ValidationException.php
│   │   └── UnauthorizedException.php
│   ├── Gateway/
│   │   ├── Provider/   (entity provider gateways live here)
│   │   └── Persister/  (entity persister gateways live here)
│   ├── Registry/
│   └── Validator/
│       ├── DomainValidatorInterface.php
│       └── AbstractLoggedUserValidator.php
├── Infrastructure/
│   ├── Controller/      (Admin/, Player/, Auth/)
│   ├── DataFixtures/
│   ├── Persister/
│   │   └── AbstractBaseMysqlPersister.php
│   ├── Repository/
│   └── Security/        (JWT user provider, voters)
└── UseCase/
    ├── UseCaseInterface.php
    ├── AbstractPublicUseCase.php
    └── AbstractLoggedUserUseCase.php
```

Key abstractions (per `conventions.md`):
- [x] `AbstractBaseMysqlPersister` — injects `EntityManagerInterface` + `ClockInterface`. Public `create($model)`, `update($model)`, `delete($model)` set `createdAt` / `updatedAt`, flush, and call protected `postCreate`/`postUpdate`/`postDelete` hooks.
- [x] `AbstractPublicUseCase` — abstract `execute(DataInputInterface $input)`, injects `DomainValidatorInterface`.
- [x] `AbstractLoggedUserUseCase` — injects `AbstractLoggedUserValidator`. The validator's per-request logged-user resolver (`getLoggedPlayer()` etc.) is left to Phase 3 once `UserDataModel` + the JWT user provider land.
- [x] `DomainValidatorInterface` — `validate(object $input): void` throws `ValidationException`.
- [x] DTO marker interfaces: `DataInputInterface`, `DataOutputInterface`, `DataModelInterface` (the latter declares timestamp property hooks `createdAt` / `updatedAt`).
- [x] Domain exceptions: `DomainException` (abstract), `EntityNotFoundException`, `ValidationException` (carries `violations` map + `errorCode`), `UnauthorizedException`.
- [x] `UseCaseInterface` (top-level contract — `execute(DataInputInterface): DataOutputInterface|list<DataOutputInterface>`).

### [x] 0.6 Verification
- [x] `docker compose up -d database` then `composer install` + `php bin/console doctrine:database:create`.
- [x] `composer qa` runs cleanly against the empty `src/`.
- [x] A throwaway commit triggers the captainhook pipeline. (Verified by running `.git/hooks/pre-commit` directly — no real commit needed; cs + stan + phpunit Unit suite all green.)

---

## Phase 1 — Domain entities (DataModels)

Goal: define the **entire** persistence schema before any feature code, so M2+ never mutates already-shipped tables.

### [x] 1.1 Entities (in dependency order)

| # | DataModel | Key fields (besides id/createdAt/updatedAt) | Notes |
|---|---|---|---|
| 1 | `MuscleDataModel` | `slug` (unique), `label` | Slug from spec list (biceps, triceps, …) |
| 2 | `EquipmentDataModel` | `slug` (unique), `label` | barbell / dumbbell / etc. |
| 3 | `MovementDataModel` | `slug` (unique), `label`, `mainMuscle` (M:1 Muscle), `secondaryMuscles` (M:N Muscle), `equipments` (M:N Equipment), tracking flags: `tracksRepetitions`, `tracksWeight`, `tracksDuration`, `tracksDistance`, `tracksInclinePercent`, `tracksInclineMeters` (all bool, default false) | |
| 4 | `UserDataModel` | `email` (unique), `password` (hashed), `roles[]` | Implements `UserInterface` + `PasswordAuthenticatedUserInterface`. **Only** DataModel allowed to import `Symfony\Component\Security`. |
| 5 | `PlayerDataModel` | `user` (1:1 User), `displayName` | Player profile attached to an account |
| 6 | `WorkoutDataModel` | `player` (M:1), `status`, `dateStart` (nullable), `dateEnd` (nullable), `plannedAt` (nullable) | |
| 7 | `ExerciseDataModel` | `workout` (M:1), `movement` (M:1), `restDurationSeconds`, `position` (int) | Ordered list of movements within a workout |
| 8 | `ExerciseSetDataModel` | `exercise` (M:1), `position`, `plannedReps`/`achievedReps`, `plannedWeight`/`achievedWeight`, `plannedDurationSeconds`/`achievedDurationSeconds`, `plannedDistanceMeters`/`achievedDistanceMeters`, `plannedInclinePercent`/`achievedInclinePercent`, `plannedInclineMeters`/`achievedInclineMeters`, `completed` (bool) | All achieved fields nullable; values stored in canonical units (kg, seconds, meters, percent). |
| 9 | `PersonalBestDataModel` | `player` (M:1), `movement` (M:1), `type` (enum string), `value` (decimal), `achievedAt`, `workout` (M:1 nullable), `exerciseSet` (M:1 nullable) | One row per (player, movement, type) — upsert on improvement |

Per-entity checkboxes:
- [x] `MuscleDataModel`
- [x] `EquipmentDataModel`
- [x] `MovementDataModel`
- [x] `UserDataModel`
- [x] `PlayerDataModel`
- [x] `WorkoutDataModel`
- [x] `ExerciseDataModel`
- [x] `ExerciseSetDataModel`
- [x] `PersonalBestDataModel`

### [x] 1.2 Registries (`Domain/Registry/...`)
- [x] `Workout/WorkoutStatusRegistry` — `PLANNED`, `IN_PROGRESS`, `COMPLETED`, `CANCELED`
- [x] `Movement/MovementTrackingFieldRegistry` — `REPETITIONS`, `WEIGHT`, `DURATION`, `DISTANCE`, `INCLINE_PERCENT`, `INCLINE_METERS`
- [x] `PersonalBest/PersonalBestTypeRegistry` — `HIGHEST_WEIGHT`, `HIGHEST_REPS`, `HIGHEST_VOLUME_ONE_SET`, `HIGHEST_VOLUME_WORKOUT`, `HIGHEST_DURATION`, `HIGHEST_DISTANCE`, `HIGHEST_SPEED`
- [x] `User/UserRoleRegistry` — `ROLE_PLAYER`, `ROLE_ADMIN` (Coach deferred)

The muscle list from the spec is **not** a registry — muscles are admin-managed reference data and the list isn't definitive. Seed the initial 20 entries via `MuscleFixtures` only.

### [~] 1.3 Gateways (interfaces only — implementations come in M2/M6)

Scope decision: **gateway interfaces are created phase by phase, alongside their first consumer**, to avoid empty / dead-code interfaces. Phase 1.3 therefore covers only the 3 admin entities (Muscle, Equipment, Movement) whose Phase 2 contexts are already known. The 6 remaining entities (User, Player, Workout, Exercise, ExerciseSet, PersonalBest) get their gateways in Phase 3 (User/Player) and Phase 6 (Workout/Exercise/ExerciseSet/PersonalBest).

Layout (deviation from earlier statement, see "Decisions / deviations" block above): files live under `Domain/Gateway/{Provider,Persister}/{SubDomain}/{Entity}/{Entity}{Provider|Persister}Gateway.php` — e.g. `Domain/Gateway/Persister/Training/Workout/WorkoutPersisterGateway.php`, `Domain/Gateway/Provider/User/PlayerProviderGateway.php`. Match the on-disk layout when adding new ones.

Persister convention: `create` and `update` return the managed `*DataModel` instance (typed narrowly per entity); `delete` returns `void`. `AbstractBaseMysqlPersister` only exposes **protected** helpers `doCreate`/`doUpdate`/`doDelete` typed `DataModelInterface`; each concrete persister in Phase 2+ implements its own public `create`/`update`/`delete` typed per `DataModel` and delegates to those helpers. PHP's variance rules forbid both narrowing a parent's `public create(DataModelInterface)` parameter to `create(MuscleDataModel)` (parameter contravariance) and inheriting the wider `DataModelInterface` return when the gateway requires `MuscleDataModel` (return covariance) — the protected-helper pattern sidesteps both.

- [x] `Provider/MuscleProviderGateway` — `findOneForAdminDetails`, `findAllForAdminList`, `findOneBySlugForUniqueness`.
- [x] `Provider/EquipmentProviderGateway` — same 3 methods.
- [x] `Provider/MovementProviderGateway` — same 3 methods.
- [x] `Persister/MusclePersisterGateway` — `create`, `update`, `delete`.
- [x] `Persister/EquipmentPersisterGateway` — `create`, `update`, `delete`.
- [x] `Persister/MovementPersisterGateway` — `create`, `update`, `delete`.
- [x] `Provider/UserProviderGateway` (Phase 3) — `findOneByEmailForAuthentication`, `findOneByEmailForUniquenessCheck`.
- [x] `Persister/UserPersisterGateway` (Phase 3) — `create`, `update`, `delete`.
- [x] `Provider/PlayerProviderGateway` (Phase 3) — `findOneByUserForLoggedPlayer`.
- [x] `Persister/PlayerPersisterGateway` (Phase 3) — `create`, `update`, `delete`.
- [x] `Provider/WorkoutProviderGateway` + `Persister/WorkoutPersisterGateway` (Phase 6.1).
- [ ] `Provider/ExerciseProviderGateway` + `Persister/ExercisePersisterGateway` (Phase 6).
- [ ] `Provider/ExerciseSetProviderGateway` + `Persister/ExerciseSetPersisterGateway` (Phase 6).
- [ ] `Provider/PersonalBestProviderGateway` + `Persister/PersonalBestPersisterGateway` (Phase 6).

### [x] 1.4 Migrations + schema doc
- [x] Run `php bin/console make:migration` once whole schema is mapped → review/clean the generated migration → `doctrine:migrations:migrate`. Initial migration: `migrations/Version20260429165952.php` (11 tables, 14 FKs).
- [x] Generate `specifications/database-schema.html` (per conventions) with a small CLI or manually rendered HTML5 view (tables + FK list). Re-generate whenever schema changes.

### [x] 1.5 Verification
- [x] Migration applies cleanly to a fresh MySQL database.
- [x] `php bin/console doctrine:schema:validate` returns "in sync".
- [x] `composer stan` passes.

---

## Phase 2 — Infrastructure for admin entities (Muscle, Equipment, Movement)

For each of the three entities — Muscle, Equipment, Movement:

- [x] `Infrastructure/Repository/{Entity}Repository extends ServiceEntityRepository implements {Entity}ProviderGateway` — context-named methods: `findOneForAdminDetails(string $id)`, `findAllForAdminList()`, `findOneBySlugForUniqueness(string $slug)`. No lazy loading — eager fetch joins for Movement (mainMuscle, secondaryMuscles, equipments).
- [x] `Infrastructure/Persister/{Entity}Persister extends AbstractBaseMysqlPersister implements {Entity}PersisterGateway`.
- [x] `Infrastructure/DataFixtures/{Entity}Fixtures` — fixtures **inject the matching `*PersisterGateway`** and call `$persister->create($model)` (never set timestamps or call `$manager->persist/flush` directly).
  - [x] `MuscleFixtures`: load all 20 slugs from the spec list (abdominal, abductors, adductors, biceps, calves, cardio, chest, forearms, full-body, glutes, hamstrings, lats, lower-back, neck, other, quadriceps, shoulders, traps, triceps, upper-back) — kept inline in the fixture, not promoted to a registry.
  - [x] `EquipmentFixtures`: minimal demo set (barbell, bike, bodyweight, dumbbell, kettlebell, machine, rower, treadmill).
  - [x] `MovementFixtures`: 10 sample movements covering every tracking-field combo (back-squat / bench-press / bicep-curl / deadlift / pull-up: reps+weight; plank: duration only; rowing: duration+distance; running / stationary-cycling: duration+distance+incline%; trail-run: duration+distance+incline%+incline_meters).

User/Player infra is deferred to Phase 3 (where it's needed for auth).

### [x] Verification
- [x] `doctrine:fixtures:load` populates the three tables (20 muscles, 8 equipments, 10 movements, 24 secondary-muscle joins, 13 equipment joins).
- [x] A repository integration test (`MovementRepositoryEagerFetchTest`) seeds a movement with 2 secondary muscles + 2 equipments, fetches it via `findOneForAdminDetails`, and asserts `mainMuscle` is not an uninitialized lazy object and the two `PersistentCollection`s are initialized.

Side-effect of running the integration suite for the first time:
- Created the `akhilleus_test` database (Symfony Flex's `dbname_suffix: '_test%env(default::TEST_TOKEN)%'` in `config/packages/doctrine.yaml`) and ran `APP_ENV=test php bin/console doctrine:migrations:migrate` against it.
- Registered `DAMA\DoctrineTestBundle\DAMADoctrineTestBundle` in `config/bundles.php` for the `test` env so that the dama PHPUnit extension actually wraps each test in a rollback'd transaction (the bundle wasn't auto-registered by Flex).

---

## Phase 3 — Authentication (just enough for Admin)

### [x] 3.1 User + Player infra
- [x] `UserRepository` (implements `UserProviderGateway`, plus Symfony `UserLoaderInterface` indirectly via security wiring) and `UserPersister` (no slug — only timestamps).
- [x] `PlayerRepository` (eager-fetches the `user` association in `findOneByUserForLoggedPlayer`) and `PlayerPersister`.
- [x] `UserFixtures` — `admin@akhilleus.test` (ROLE_ADMIN, password `AdminAdmin1!`) and `player@akhilleus.test` (ROLE_PLAYER, password `PlayerHero1!`) plus the linked `PlayerDataModel` (display name "Player Hero"). Passwords are hashed via `UserPasswordHasherInterface` before persisting. Fixtures bundle is dev/test-gated by `config/bundles.php`.

### [x] 3.2 Security configuration
- [x] `config/packages/security.yaml`:
  - [x] Password hasher: `auto` (already shipped by Phase 0; reduced cost for `when@test`).
  - [x] Provider: custom `App\Infrastructure\Security\UserProvider` backed by `UserProviderGateway::findOneByEmailForAuthentication`.
  - [x] Firewalls: `security` (`pattern: ^/api/security`, `stateless: true`, `json_login` on `/api/security/login` with email/password paths, success/failure handlers from Lexik); `api` (`pattern: ^/api`, `stateless: true`, `jwt: ~`).
  - [x] Access control: `^/api/security` → `PUBLIC_ACCESS`, `^/api/admin` → `ROLE_ADMIN`, `^/api/player` → `ROLE_PLAYER`.
- [x] Lexik JWT bundle: keypair generated via `php bin/console lexik:jwt:generate-keypair` (`config/jwt/private.pem` + `public.pem`, gitignored). `lexik_jwt_authentication.yaml` reads keys + passphrase from env (`.env`).
- [x] Stub `App\Infrastructure\Controller\Security\SecurityController::login()` advanced from Phase 3.3 — only purpose is to register the `/api/security/login` route in the routing layer (the JSON-login authenticator intercepts before the action runs). The `register` and `logout` actions land in 3.3.

### [x] 3.3 Auth UseCases & Controller
- [x] `UseCase/User/RegisterPlayerUseCase` (extends `AbstractPublicUseCase`) — actual on-disk path is `UseCase/User/`, not `UseCase/Auth/` as the original plan called for.
  - [x] `Domain/DTO/DataInput/User/RegisterPlayerDataInput` (email, plainPassword, displayName) → `Domain/DTO/DataOutput/User/RegisterPlayerDataOutput` (playerId, email, displayName). Same path correction (`User/` not `Auth/`).
  - [x] `Domain/Validator/User/RegisterPlayerValidator` (implements `DomainValidatorInterface`, injects `UserProviderGateway`): email format (FILTER_VALIDATE_EMAIL), email unique (`findOneByEmailForUniquenessCheck`), displayName not empty (after `trim`), password ≥ 8 chars + ≥ 1 uppercase + ≥ 1 lowercase + ≥ 1 digit + ≥ 1 special char (regex). Accumulates all violations into a `ValidationException` with `errorCode: REGISTER_PLAYER_VALIDATION_FAILED`.
  - [x] Use case delegates to `PlayerPersister::create(RegisterPlayerDataInput)` which orchestrates `UserPersister::create($user)` (with `ROLE_PLAYER`, `plainPassword` set on the model — `UserPersister` hashes it) then `doCreate($player)` linked to the freshly persisted user.
- [x] Login is handled entirely by Lexik's JSON login authenticator — no UseCase needed.
- [x] `Infrastructure/Controller/Security/SecurityController`:
  - [x] `POST /api/security/registration` — calls `RegisterPlayerUseCase`, returns **201** with `{playerId, email, displayName}`. On invalid input the `DomainExceptionListener` maps `ValidationException` → **422** with `{message, errorCode, violations}`.
  - [x] `POST /api/security/login` — anchored by the stub action; the JSON-login authenticator on the `security` firewall does the work and returns a JWT.
  - [x] `POST /api/security/logout` — returns **204 No Content** (clients discard the token; placeholder for future blocklist).
- [x] `Infrastructure/Controller/DomainExceptionListener` (advanced from Phase 4 — needed for 3.3 to surface validation errors as 422 rather than 500): `#[AsEventListener]` on `ExceptionEvent`, maps `ValidationException` → 422, `EntityNotFoundException` → 404, `UnauthorizedException` → 401.

### [~] 3.4 Verification
- [x] `curl POST /api/security/registration` with valid input → 201 with `{playerId, email, displayName}`.
- [x] `curl POST /api/security/registration` with invalid email + weak password + empty displayName → 422 with structured violations per field.
- [x] `curl POST /api/security/registration` with an email that already exists → 422 with `email: ["An account with this email already exists."]`.
- [x] `curl POST /api/security/login` with the freshly-registered credentials → 200 with a JWT (`roles: ["ROLE_PLAYER"]`).
- [x] `curl POST /api/security/logout` → 204.
- [ ] `GET /api/admin/me` (deferred to Phase 4 — endpoint does not exist yet; admin/player JWT 200/403 split will be tested when admin controllers land).

---

## Phase 4 — Admin REST API (Equipment, Muscle, Movement CRUD)

Layout follows the DataModel sub-domain pattern: `UseCase/Admin/Training/{Equipment,Muscle,Movement}/`, mirrored in `Domain/DTO/{DataInput,DataOutput}/Admin/Training/{Entity}/`, `Domain/Validator/Admin/Training/{Entity}/`, and `Infrastructure/Controller/Admin/Training/`.

**Foundation (Stage A)** — auth resolver:
- [x] `Domain/Security/LoggedUserResolverInterface` + `Infrastructure/Security/LoggedUserResolver` (consumes `Symfony\Bundle\SecurityBundle\Security`).
- [x] `Domain/Validator/AbstractLoggedUserValidator` upgraded — injects the resolver and exposes `final protected getLoggedUser(): UserDataModel`. All admin validators extend it.
- [x] `Domain/Validator/EmptyDomainValidator` — shared no-op validator for `List` / `Get` use cases that have no input rules. Single unit test.
- [x] `Domain/DataTransformer/StringDataTransformerInterface` extracted so Domain validators can `slugify` without depending on Infrastructure (the existing `Infrastructure/DataTransformer/StringDataTransformer` now `implements` it).
- [x] `AbstractPublicUseCase::execute` widened to `DataOutputInterface|array` to match `UseCaseInterface` and let `List...UseCase` return `list<DataOutput>`.

For each of the three entities (Equipment, Muscle, Movement), five UseCases under `UseCase/Admin/Training/{Entity}/`:

- [x] `Create{Entity}UseCase` (extends `AbstractLoggedUserUseCase`).
- [x] `Update{Entity}UseCase` (extends `AbstractLoggedUserUseCase`).
- [x] `Delete{Entity}UseCase` (extends `AbstractLoggedUserUseCase`).
- [x] `List{Entity}sUseCase` (extends `AbstractPublicUseCase` + `EmptyDomainValidator`; the JWT firewall + `^/api/admin` access_control rule already gate access by ROLE_ADMIN).
- [x] `Get{Entity}DetailsUseCase` (extends `AbstractPublicUseCase` + `EmptyDomainValidator`; throws `EntityNotFoundException` on miss).

DTOs (`Domain/DTO/...`):
- [x] `DataInput/Admin/Training/{Entity}/Create{Entity}DataInput`
- [x] `DataInput/Admin/Training/{Entity}/Update{Entity}DataInput`
- [x] `DataInput/Admin/Training/{Entity}/Delete{Entity}DataInput`
- [x] `DataInput/Admin/Training/{Entity}/Get{Entity}DetailsDataInput`
- [x] `DataInput/Admin/Training/{Entity}/List{Entity}sDataInput` (empty for now; reserved for pagination/filter)
- [x] `DataOutput/Admin/Training/{Entity}/{Entity}DataOutput`
- [x] `DataOutput/Admin/Training/{Entity}/{Entity}ListItemDataOutput`
- [x] `DataOutput/Admin/Training/{Entity}/Delete{Entity}DataOutput` (`{deletedId}`)

Validators (`Domain/Validator/Admin/Training/{Entity}/`):
- [x] `Create{Entity}Validator`, `Update{Entity}Validator`, `Delete{Entity}Validator` (all extend `AbstractLoggedUserValidator`).
- [x] Movement-specific rules: at least one tracking field true; main muscle exists; every secondary muscle exists; every equipment exists; slug derived from label is unique (allowing self-match on Update).

Controllers (`Infrastructure/Controller/Admin/Training/`):
- [x] `EquipmentAdminController`, `MuscleAdminController`, `MovementAdminController` — each with the standard 5 routes:
  - [x] `GET    /api/admin/{plural}` (list)
  - [x] `GET    /api/admin/{plural}/{id}` (details)
  - [x] `POST   /api/admin/{plural}` (create) → 201
  - [x] `PUT    /api/admin/{plural}/{id}` (update)
  - [x] `DELETE /api/admin/{plural}/{id}` → 204

Cross-cutting:
- [x] `Infrastructure/Controller/DomainExceptionListener` — already advanced from this phase during Phase 3.3. Maps `ValidationException` → 422 (with structured `violations` + `errorCode`), `EntityNotFoundException` → 404, `UnauthorizedException` → 401.
- [ ] OpenAPI annotations on each controller (NelmioApiDoc) — deferred. Bundle is installed but not registered. Hook in when Phase 5 React Admin needs `/api/doc.json`.

Dev-only seed:
- [x] `Infrastructure/DataFixtures/User/AdminFixtures` — `admin@akhilleus.test` / `AdminAdmin1!` (ROLE_ADMIN). Bypasses the "user is created via a higher-level persister (Player / Admin / Coach)" rule for now since no `AdminPersister` exists yet. To replace by a proper RegisterAdmin path once the admin onboarding flow lands.

### [x] Verification
- [x] One Integration test class per UseCase (15 total) — `tests/Integration/UseCase/Admin/Training/{Entity}/{Name}UseCaseTest.php`. Each test resolves the use case from the container and exercises happy path + at least one error branch (validation, not-found, or role).
- [x] One Unit test class per Validator (9 total) — `tests/Unit/Domain/Validator/Admin/Training/{Entity}/{Name}ValidatorTest.php`. Mocks the gateways and `LoggedUserResolverInterface`, exercises every rule.
- [x] cURL smoke flow: admin login → JWT; `GET /api/admin/equipments` 200; `POST` 201 with payload; `POST` with empty label → 422 with `violations.label`; same path with player JWT → 403; same without JWT → 401. Movement create → details (verifies nested mainMuscle / secondaryMuscles / equipments serialization) → delete 204 → details after delete 404. All green.
- [x] `composer qa` green: cs ✅, stan ✅, phpunit ✅ (81 tests / 153 assertions).

---

## Phase 5 — Admin frontend (React + TypeScript + Ant Design)

**Stack pivot from the original plan**: we deliberately drop react-admin and build a regular React TS app. Rationale: full control over UX, no abstraction lock-in, and the same Vite + react-router + TanStack Query + Ant Design building blocks will be reused for the player site (same component library, same patterns). The convention is **small, focused, reusable components** — no monolithic multi-task pages. Light / dark theme toggle is required and persists across sessions.

### [x] 5.1 Foundation

- [x] Add a Node 22 service `frontend-admin` to `compose.yaml` (built from `docker/node/Dockerfile`), bind-mounting `./frontend/admin`, exposing the Vite dev port (5173). Run `npm run dev` inside the container so the host doesn't need Node installed.
- [x] Bootstrap `frontend/admin/` with Vite + React 18 + TypeScript. Toolchain: ESLint + Prettier, strict TS, npm scripts (`dev`, `build`, `typecheck`, `lint`, `lint:fix`).
- [x] Dependencies: `react`, `react-dom`, `react-router-dom`, `antd`, `@ant-design/icons`, `@tanstack/react-query`, `@tanstack/react-query-devtools` (dev only). No styling library beyond antd's tokens.

### [x] 5.2 App-level concerns

- [x] **Theme**: `ThemeProvider` (custom) wraps antd's `ConfigProvider` and switches between `theme.defaultAlgorithm` (light) and `theme.darkAlgorithm` (dark). Mode persisted in `localStorage`. Toggle button in the app header.
- [x] **Auth**: `AuthContext` + `useAuth()` hook. `login(email, password)` POSTs `/api/security/login` and stores the JWT. `logout()` POSTs `/api/security/logout` and clears storage. JWT lives in `localStorage`. On any 401 from the API client, the auth context clears storage and forces a redirect to `/login`.
- [x] **API client**: `httpClient.ts` — fetch wrapper that injects `Authorization: Bearer <jwt>`, JSON-encodes/decodes, throws typed errors on 4xx/5xx (carrying the backend `{message, errorCode, violations}` payload for 422). Per-resource hooks built on TanStack Query (`useEquipmentsQuery`, `useCreateEquipmentMutation`, …). Server URL configurable via `VITE_API_BASE_URL` env (defaults to `https://127.0.0.1:8000`).
- [x] **Routing**: `react-router-dom` with `<ProtectedRoute />` that redirects unauthenticated visitors to `/login`. Public routes: `/login`. Protected routes: `/`, `/equipments`, `/equipments/new`, `/equipments/:id`, same for muscles and movements.

### [x] 5.3 Layout + reusable components

- [x] `<AppLayout />` — antd `<Layout>` with `<Sider>` (nav links: Equipments, Muscles, Movements), `<Header>` (theme toggle + logout button + admin display name), `<Content>` for the route.
- [x] `<DataTable />` — generic antd `<Table>` wrapper, takes a column config + a TanStack query and renders the list with a loading state and a row-action column (Edit / Delete).
- [x] `<EntityForm />` — antd `<Form>` wrapper that handles submit, surfaces backend `violations` per field (mapped to antd field errors), shows success / error feedback via `notification` (shipped as `<EntityFormShell />`).
- [x] `<DeleteConfirmButton />` — antd `<Popconfirm>` + delete mutation hook.
- [x] `<LoadingState />`, `<ErrorState />` — small atomic display components used everywhere.

### [x] 5.4 Resources

For each resource, the page set lives under `src/features/{entity}/`:

- [x] `equipments` — `EquipmentListPage`, `EquipmentCreatePage`, `EquipmentEditPage`. Form has a single `label` input. List shows `label` + actions (slug column dropped per the admin UX iteration).
- [x] `muscles` — same shape as equipments.
- [x] `movements` — `MovementForm` adds: `Select` (single) for `mainMuscleId`, `Select` (multiple) for `secondaryMuscleIds`, `Select` (multiple) for `equipmentIds`, 6 `Checkbox` for the tracking flags, at-least-one-tracking-flag client-side mirror of the backend rule.

Cross-cutting iterations done in Phase 5 that are not in the original plan but are now part of the working contract:
- Sort UI: each list page has a clickable Label header (`sorter: true` + controlled `sortOrder`) wired to a `direction` state, passed to the list query as `?sort=label&direction=…`. Backend has `ALLOWED_SORTS` const at the DataInput level + a `List*Validator` rejecting unknown sort/direction with a structured 422.
- DataInput convention for list endpoints renamed `orderBy` → `sort`. `EmptyDomainValidator` removed; each Get*Details and List* use case has its own validator.

### [x] 5.5 Verification
- [x] `docker compose up -d frontend-admin` then visit the dev URL; log in with `admin@akhilleus.test` / `AdminAdmin1!`; CRUD all three resources end-to-end (with the dark theme toggled at least once during the run).
- [x] `npm run typecheck` + `npm run lint` + `npm run build` pass clean.

---

## ⏸ Checkpoint — admin path complete

At this point an admin can log in and manage Equipment / Muscle / Movement reference data through the React + AntD admin UI, backed by a tested REST API and a fixture-seeded MySQL database. **Pause here** — the player website work is a separate stage.

---

## Phase 6 — Player REST API

### [~] 6.1 Workout creation & lifecycle
UseCases under `UseCase/Player/Training/Workout/` (sub-domain folder used to mirror the DataModel layout):
- [x] `StartEmptyWorkoutUseCase` — creates `WorkoutDataModel` with `status=IN_PROGRESS`, `dateStart=now`.
- [x] `PlanWorkoutUseCase` — creates `WorkoutDataModel` with `status=PLANNED`, `plannedAt=$input->plannedAt`. Validator (`PlanWorkoutValidator`) enforces "plannedAt must be in the future" via `ClockInterface`.
- [x] `StartPlannedWorkoutUseCase` — transitions PLANNED → IN_PROGRESS, sets `dateStart=now`. Throws `EntityNotFoundException` (404) if the id is unknown for the logged player; `ValidationException` with `errorCode: START_PLANNED_WORKOUT_ILLEGAL_STATE` (422) if the workout is not in PLANNED.
- [x] `CancelWorkoutUseCase` — PLANNED|IN_PROGRESS → CANCELED. Same 404/422 split (`errorCode: CANCEL_WORKOUT_ILLEGAL_STATE`).
- [ ] `FinishWorkoutUseCase` — see §6.3.

Foundations introduced in 6.1 (apply to all subsequent player phases):
- [x] **Admin/Player split of the logged-user abstracts.** `AbstractLoggedUser{Validator,UseCase}` removed; replaced by `AbstractLoggedAdmin{Validator,UseCase}` (exposes `getLoggedAdmin(): UserDataModel`) and `AbstractLoggedPlayer{Validator,UseCase}` (exposes `getLoggedPlayer(): PlayerDataModel`). All 9 admin validators + 9 admin use cases migrated.
- [x] **`Domain/Security/LoggedPlayerResolverInterface`** + `Infrastructure/Security/LoggedPlayerResolver` (composes `LoggedUserResolverInterface` + `PlayerProviderGateway`; throws `UnauthorizedException` if the authenticated user has no player profile).
- [x] **`WorkoutProviderGateway::findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?WorkoutDataModel`** scopes lookups to the logged player at the gateway level — controllers/use cases never need to compare ids manually.
- [x] **`WorkoutPersisterGateway` + `WorkoutPersister`** (no slug/computed fields, just `create/update/delete` over `AbstractBaseMysqlPersister`).
- [x] **Shared `WorkoutDataOutput`** under `Domain/DTO/DataOutput/Player/Training/Workout/` returned by all 4 use cases (`id`, `status`, `plannedAt?`, `dateStart?`, `dateEnd?` as `\DateTimeImmutable` — controllers will JSON-serialize at the edge).
- [x] **Tests**: 4 validator unit tests + 4 use case integration tests under `tests/{Unit,Integration}/UseCase/Player/Training/Workout/`. Integration tests build the use case manually with a stub `LoggedPlayerResolverInterface` (the resolver always needs replacement; DI alone wouldn't help). They use `EntityManagerInterface` + `ManagerRegistry` from the public Doctrine bindings to instantiate `WorkoutPersister` / `WorkoutRepository` directly. With the controller now landed (see 6.5), the gateways are no longer pruned, but the manual pattern is kept since the resolver injection remains a test-time concern.
- [x] **Controller**: `Infrastructure/Controller/Player/Training/WorkoutPlayerController` — 4 endpoints under `/api/player/workouts*`, all `POST`. Routes: `POST /api/player/workouts` (start empty, 201), `POST /api/player/workouts/planned` (plan, 201, body `{plannedAt}`), `POST /api/player/workouts/{id}/start` (start a planned), `POST /api/player/workouts/{id}/cancel`. Body parsing of `plannedAt` to `\DateTimeImmutable` is done in the controller; failures throw `ValidationException` with `errorCode: PLAN_WORKOUT_BODY_INVALID` (so the same DomainExceptionListener surfaces a 422 with structured violations).
- [x] **cURL smoke flow**: login player → start empty (201) → plan future (201) → plan past date (422 `PLAN_WORKOUT_VALIDATION_FAILED`) → plan empty body (422 `PLAN_WORKOUT_BODY_INVALID`) → start unknown id (404) → start a planned (200) → cancel (200) → cancel a CANCELED (422 `CANCEL_WORKOUT_ILLEGAL_STATE`) → no token (401) → admin token (403). All green.

### [x] 6.2 Workout content
UseCases under `UseCase/Player/Training/Exercise/` and `UseCase/Player/Training/ExerciseSet/` (sub-domain folder used to mirror the DataModel layout, same as 6.1):
- [x] `AddMovementToWorkoutUseCase` — auto-assigns next position; uses `MovementProviderGateway::findOneByIdForExerciseAttachment` (added in this batch).
- [x] `RemoveMovementFromWorkoutUseCase`
- [x] `UpdateMovementRestDurationUseCase`
- [x] `ReorderMovementsUseCase` — keeps the input-vs-existing id-set mismatch check inside the use case (it needs `ExerciseProviderGateway::findAllByWorkoutIdForPlayerAction` results); the validator handles ownership + state + format.
- [x] `AddExerciseSetUseCase` (planned values) — auto-assigns next position; validator rejects planned values for fields the movement does not track (`TRACKING_MISMATCH_ERROR_CODE`).
- [x] `UpdateExerciseSetPlannedUseCase`
- [x] `UpdateExerciseSetAchievedUseCase` — only allowed on `IN_PROGRESS` workouts (vs Update*Planned which is allowed on `PLANNED|IN_PROGRESS`).
- [x] `RemoveExerciseSetUseCase`
- [x] `MarkExerciseSetCompletedUseCase`

Foundations introduced in 6.2:
- [x] **Phase 1.3 leftover gateways landed**: `ExerciseProviderGateway` + `ExercisePersisterGateway` + `ExerciseSetProviderGateway` + `ExerciseSetPersisterGateway` (interfaces) and their concrete `ExerciseRepository` / `ExercisePersister` / `ExerciseSetRepository` / `ExerciseSetPersister`. Each provider exposes `findOneByIdForPlayerAction` (player-scoped 404) + `findAllBy{Workout,Exercise}IdForPlayerAction`.
- [x] **`OwnedByPlayerInterface`** (`Domain/DTO/DataModel/OwnedByPlayerInterface.php`) — implemented by `WorkoutDataModel` (direct property) and by `ExerciseDataModel` / `ExerciseSetDataModel` via virtual property hooks (`public PlayerDataModel $player { get => $this->workout->player; }` etc.). Enables `assertPlayerOwns()` defensively in player-edit validators.
- [x] **`AddMovementToWorkoutUseCase` syncs `$workout->exercises->add($created)` after persist** because Doctrine's identity map returns the cached workout on subsequent `findOneByIdForPlayerAction` calls without refreshing the inverse collection. Without that sync, three consecutive adds in a transaction collide on position 0.
- [x] **`Workout::$exercises ↔ Exercise::$workout` is now a fully bidirectional relation**: `inversedBy: 'exercises'` added on the owning side. `doctrine:schema:validate` is fully green (mapping + DB).
- [x] **Cross-cutting validator/use-case norm refactor** (also touches all earlier phases — see "Validator/UseCase norm refactor (Phase 6.2)" entry in the Decisions / deviations block below). Every existing use case in the project was migrated.

### [~] Cross-cutting Verification (Phase 6.2)
- [x] One Unit test class per Validator (28 total now: Workout × 4, Admin × 9, Exercise × 4, ExerciseSet × 5, Register × 1, plus the 5 simpler ones for Add/List that don't need entity stubs).
- [x] One Integration test class per UseCase under `tests/Integration/UseCase/Player/Training/{Exercise,ExerciseSet}/`. Player tests instantiate the use case manually with a stubbed `LoggedPlayerResolverInterface` (canonical reference: `StartEmptyWorkoutUseCaseTest`).
- [ ] cURL smoke flow for the 9 endpoints (deferred to Phase 6.3 batch since FinishWorkout caps the lifecycle).

### [x] 6.3 Finishing a workout & personal bests
- [x] `FinishWorkoutUseCase` (under `UseCase/Player/Training/Workout/`):
  1. [x] Reload workout with all sets eager-fetched via `WorkoutProviderGateway::findOneByIdForFinishWorkout` (joins `e.exerciseSets`, `e.movement`, ordered by `e.position` then `s.position`).
  2. [x] Validator (`FinishWorkoutValidator`, `validate(player, input, workout)`): ownership + status === IN_PROGRESS + all `completed` flags set. Otherwise throws `ValidationException` with code `WORKOUT_HAS_INCOMPLETE_SETS` and `violations.exerciseSets` carrying the list of incomplete set ids — frontend renders the spec's modal from this payload.
  3. [x] Use case sets `dateEnd=now`, `status=COMPLETED`, persists workout, then evaluates personal bests.
  4. [x] Delegates to `Domain/Service/PersonalBestEvaluator` (pure domain — no clock dep, uses `$workout->dateEnd` as `achievedAt`) which receives the workout + a `PersonalBestProviderGateway` and returns `list<PersonalBestUpsert>`. The use case persists each via `PersonalBestPersisterGateway::create` or `::update` based on `$upsert->isNew`.
- [x] `PersonalBestEvaluator` computes the seven categories from the spec, all with scale-4 numeric strings:
  - [x] HIGHEST_WEIGHT — `max(achievedWeight)` per movement (only if `tracksWeight`).
  - [x] HIGHEST_REPS — `max(achievedReps)` (only if `tracksRepetitions`).
  - [x] HIGHEST_VOLUME_ONE_SET — `max(achievedReps * achievedWeight)` (only if both).
  - [x] HIGHEST_VOLUME_WORKOUT — `sum(achievedReps * achievedWeight)` for that movement across all the workout's sets (only if both); has no `exerciseSet` provenance (workout-level).
  - [x] HIGHEST_DURATION — `max(achievedDurationSeconds)` (only if `tracksDuration`).
  - [x] HIGHEST_DISTANCE — `max(achievedDistanceMeters)` (only if `tracksDistance`).
  - [x] HIGHEST_SPEED — `max(achievedDistanceMeters / achievedDurationSeconds)` (only if both, sets with `duration === 0` are skipped). **Formula confirmed `distance / duration`** (spec's `duration * distance` was a typo — that would be volume, not speed).
- [x] A category only fires if the underlying movement tracks the relevant fields. The evaluator iterates each Exercise → ExerciseSet, groups by `movement->id`, and computes per-movement candidates so a movement appearing in multiple Exercises in the same workout aggregates correctly.

Foundations introduced in 6.3:
- [x] **Phase 1.3 leftover gateways landed**: `PersonalBestProviderGateway::findOneForPlayerMovementType(player, movement, type): ?PersonalBestDataModel` + `PersonalBestPersisterGateway` + `PersonalBestRepository` + `PersonalBestPersister`.
- [x] **`PersonalBestDataModel` implements `OwnedByPlayerInterface`** (already had `public PlayerDataModel $player`, just added the interface).
- [x] **`Exercise::$exerciseSets` inverse 1:M added** (Phase 1 deferral resolved). `ExerciseSet::$exercise` now has `inversedBy: 'exerciseSets'`. Both bidirectional relations on the Exercise/Workout chain are now fully wired (previous one was Workout↔Exercise in 6.2). `doctrine:schema:validate` clean (mapping + DB).
- [x] **`PersonalBestEvaluator` uses native PHP float arithmetic** (not bcmath, which is not in the dev environment). Domain values (weight ≤ 9999.99 kg, distance ≤ 99,999,999.99 m, etc.) are all well within IEEE 754's 15–16 significant digit precision. Final values are formatted with `number_format(..., 4, '.', '')` to match the `personal_best.value` column scale of 4.
- [x] **`PersonalBestUpsert`** small DTO under `Domain/Service/` to disambiguate create vs update without requiring the use case to inspect Doctrine state. Returned as `list<PersonalBestUpsert>` from `evaluate()`.
- [x] **Strictly-greater-than improvement test**: ties do not produce upserts (verified by `PersonalBestEvaluatorTest::testItDoesNotProduceUpsertOnATieWithExistingPB` + integration `testATieWithExistingPBDoesNotProduceADuplicate`).

Tests:
- [x] `tests/Unit/Domain/Service/PersonalBestEvaluatorTest` — 7 tests covering: missing dateEnd → LogicException, full strength-movement happy path, full cardio-movement happy path, speed skipped when duration=0, tie does not produce upsert, beat updates the existing row in place, aggregation across exercises that share the same movement.
- [x] `tests/Unit/Domain/Validator/Player/Training/Workout/FinishWorkoutValidatorTest` — 6 tests covering happy path, no-set workout, ownership (UnauthorizedException), wrong status (PLANNED, COMPLETED), and the WORKOUT_HAS_INCOMPLETE_SETS path with id list.
- [x] `tests/Integration/UseCase/Player/Training/Workout/FinishWorkoutUseCaseTest` — 6 tests covering full happy path with PB persistence + reread, tie scenario across two workouts, beat scenario (verifies the existing PB row is updated in place via id comparison), incomplete-sets rejection, planned-state rejection, unknown-id 404.

Controller:
- [x] `WorkoutPlayerController::finish` — `POST /api/player/workouts/{id}/finish`. No body required.

### [x] 6.4 Read endpoints
UseCases live under `UseCase/Player/Training/{Workout,PersonalBest}/` (sub-domain folders match the on-disk layout used since 6.1, not the original `UseCase/Player/Read/`):
- [x] `ListWorkoutHistoryUseCase` — completed workouts for the logged player, paginated (`{page, perPage}` input with `MAX_PER_PAGE = 100`), ordered by `dateEnd DESC`. Returns `WorkoutHistoryDataOutput {items, page, perPage, totalCount}`. Has a validator (`ListWorkoutHistoryValidator`) for pagination bounds; throws `LIST_WORKOUT_HISTORY_VALIDATION_FAILED`.
- [x] `ListUpcomingWorkoutsUseCase` — returns `list<WorkoutDataOutput>` of `status IN (PLANNED, IN_PROGRESS)`. Ordered: PLANNED first by `plannedAt ASC`, then IN_PROGRESS by `dateStart DESC`. Implementation leverages MySQL string ordering of the status enum (`'PLANNED' > 'IN_PROGRESS'` alphabetically with DESC).
- [x] `GetWorkoutDetailsUseCase` — full hydrate (workout + exercises + movement + main muscle + sets) via `WorkoutProviderGateway::findOneByIdForDetails` (replaces the previous `findOneByIdForFinishWorkout`; both finish and details share the same eager-fetch query). Returns `WorkoutDetailsDataOutput` composing `ExerciseDetailsDataOutput` (which itself reuses the existing `ExerciseMovementDataOutput` + `ExerciseSetDataOutput` from 6.2). Throws 404 if unknown id or owned by another player.
- [x] `ListPersonalBestsUseCase` — `list<PlayerMovementPersonalBestsDataOutput>` grouped by movement. Each bucket has a `MovementSummaryDataOutput` (id, slug, label, mainMuscleSlug) and a `list<PersonalBestEntryDataOutput>` (type, value, achievedAt, workoutId, exerciseSetId). Backed by `PersonalBestProviderGateway::findAllByPlayerForList` which eager-fetches movement + main muscle and orders by `m.label ASC, p.type ASC`.

Foundations introduced in 6.4:
- [x] **Gateway methods** added to `WorkoutProviderGateway`: `findOneByIdForDetails` (renamed from `findOneByIdForFinishWorkout`, joins now include `m.mainMuscle`), `findCompletedByPlayer(player, page, perPage)`, `countCompletedByPlayer(player)`, `findPlannedOrInProgressByPlayer(player)`. Added to `PersonalBestProviderGateway`: `findAllByPlayerForList(player)`.
- [x] **Routing for `GET /api/player/workouts/{id}` constrained by ULID regex** (`[0-9A-HJKMNP-TV-Z]{26}`) so it doesn't shadow `/history` or `/upcoming` even if they were defined after it. Symfony's first-match still picks the named routes thanks to declaration order, but the constraint is defence-in-depth.
- [x] **`PersonalBestPlayerController`** new controller, `GET /api/player/personal-bests`.

Tests:
- [x] `tests/Unit/Domain/Validator/Player/Training/Workout/ListWorkoutHistoryValidatorTest` — happy path, lower bound, upper bound, page<1 / perPage<1 / perPage>MAX, accumulation.
- [x] 4 integration tests (one per use case) under `tests/Integration/UseCase/Player/Training/{Workout,PersonalBest}/` — covering ordering, pagination, cross-player isolation, 404 paths, grouping by movement.

Controllers:
- [x] `WorkoutPlayerController::history` — `GET /api/player/workouts/history?page=1&perPage=20`.
- [x] `WorkoutPlayerController::upcoming` — `GET /api/player/workouts/upcoming`.
- [x] `WorkoutPlayerController::details` — `GET /api/player/workouts/{id}` (ULID-constrained).
- [x] `PersonalBestPlayerController::list` — `GET /api/player/personal-bests`.

### [x] 6.5 Controllers
Under `Infrastructure/Controller/Player/`. Controllers are landed **per Phase 6.x batch** (alongside the use cases they expose) rather than all in one final pass — that lets us cURL-smoke each phase end-to-end and avoids the DI-container pruning that bites integration tests when a use case has no public consumer yet.
- [x] `Training/WorkoutPlayerController` — start-empty / plan (6.1), start-planned / cancel (6.1), finish (6.3), history / upcoming / details (6.4).
- [x] `Training/ExercisePlayerController` — add (`POST /api/player/workouts/{workoutId}/exercises`), remove (`DELETE /api/player/exercises/{id}`), update rest (`PUT /api/player/exercises/{id}/rest-duration`), reorder (`POST /api/player/workouts/{workoutId}/exercises/reorder`).
- [x] `Training/ExerciseSetPlayerController` — add (`POST /api/player/exercises/{exerciseId}/sets`), update planned (`PUT /api/player/sets/{id}/planned`), update achieved (`PUT /api/player/sets/{id}/achieved`), remove (`DELETE /api/player/sets/{id}`), mark complete (`POST /api/player/sets/{id}/complete`). Body parsing of numeric-string fields surfaces malformed values as 422 `EXERCISE_SET_BODY_INVALID` (controller-level guard before the use case is hit).
- [ ] `Training/PersonalBestPlayerController` — list (6.4).

All routes under `/api/player/*`, `ROLE_PLAYER` required (already gated by `config/packages/security.yaml`). Player use cases extend `AbstractLoggedPlayerUseCase` and resolve the current `PlayerDataModel` via `LoggedPlayerResolverInterface`.

Date serialization: `WorkoutDataOutput` uses `?string` (ISO 8601 / RFC 3339 / `\DateTimeInterface::ATOM`) for date fields rather than typed `\DateTimeImmutable`. Decision: `JsonResponse` calls `json_encode` directly, which would dump `\DateTimeImmutable` as `{date, timezone_type, timezone}` — ugly for API consumers. Use cases format dates at the DTO boundary with `?->format(\DateTimeInterface::ATOM)`. Apply the same pattern to every Player DataOutput that carries date fields.

### [x] 6.6 Verification
- [x] **Per-UseCase Integration tests** ship per phase (Workout × 4 in 6.1, Exercise × 4 + ExerciseSet × 5 in 6.2, FinishWorkout in 6.3, read × 4 in 6.4) — covered by individual integration tests plus the full-lifecycle test below.
- [x] **`PlayerWorkoutLifecycleTest`** (`tests/Integration/UseCase/Player/Training/`) — single end-to-end test that walks Start → AddMovement → AddSet × 2 → UpdateAchieved → MarkCompleted → Finish → assert PBs persisted, then queries history + details + personal-bests to verify the read endpoints reflect the persisted state. Catches composition regressions (e.g., a use case forgetting to sync inverse collections). 29 assertions in a single test method.
- [x] **`FinishWorkoutUseCaseTest`** covers the `WORKOUT_HAS_INCOMPLETE_SETS` path, the tie-doesn't-duplicate scenario, and the beat-updates-in-place scenario (verifies the existing PB row id stays the same after an improvement).
- [x] **cURL smoke flow** — full HTTP path against `symfony server:start -d`, hitting each Phase 6 endpoint with the seeded `player@akhilleus.test` JWT:
  - 6.1: `POST /api/player/workouts` → cancel, `POST /api/player/workouts/planned` → start (200/200/201/200).
  - 6.2: `POST /api/player/workouts/{id}/exercises`, `POST /api/player/exercises/{id}/sets` × 2, `PUT /api/player/sets/{id}/achieved` × 2, `POST /api/player/sets/{id}/complete` × 2.
  - 6.3: `POST /api/player/workouts/{id}/finish` returns `{workout, newPersonalBests: [HIGHEST_WEIGHT, HIGHEST_REPS, HIGHEST_VOLUME_ONE_SET, HIGHEST_VOLUME_WORKOUT]}`.
  - 6.4: `GET /api/player/workouts/history` (paginated), `/upcoming`, `/{id}` (details), `/api/player/personal-bests` — all 200, JSON shape matches the DataOutput contracts.
  - Auth paths: no JWT → 401; admin JWT on `/api/player/*` → 403.

### Foundation introduced in 6.6
- [x] **`AddExerciseSetUseCase` syncs `$exercise->exerciseSets->add($created)` after persist** (same pattern as `AddMovementToWorkoutUseCase`'s exercises sync from 6.2). Required for the lifecycle test where the same EM scope chains AddSet → MarkCompleted → Finish; without the sync, the cached exercise instance has a stale `exerciseSets` collection by the time `findOneByIdForDetails` runs the eager-fetch (Doctrine returns the cached entity from the identity map, the join data goes into the new rows but the cached collection is not rebuilt).

---

## Phase 7 — Player frontend (React TS)

- [ ] Bootstrap `frontend/website/` with Vite + React 18 + TypeScript + React Router + TanStack Query (light, no Redux) + a small fetch wrapper that handles JWT.
- [ ] D&D / medieval-fantasy theming: define CSS variables in `src/theme/colors.css` (`--color-parchment`, `--color-ink`, `--color-gold`, `--color-banner`, `--color-iron`, `--color-blood`, plus dark/light toggle); apply via `:root`. Pick a serif/blackletter display font + a readable body font.
- [ ] Routes:
  - [ ] `/login`, `/register` — public.
  - [ ] `/` — dashboard: "Start workout", "Plan workout", upcoming list (top 3), recent history (top 3).
  - [ ] `/workouts/new` — choose start vs plan flow.
  - [ ] `/workouts/:id` — workout editor / live view (planned vs in-progress vs completed states share a layout but different controls).
  - [ ] `/history` — paginated past workouts.
  - [ ] `/upcoming` — list of planned/in-progress.
  - [ ] `/achievements` — personal bests grouped by movement.
- [ ] Generated TS client: run `openapi-typescript` against `/api/doc.json` into `src/api/types.ts` to keep DTOs in sync.
- [ ] Finish-workout flow: if API returns 422 with `WORKOUT_HAS_INCOMPLETE_SETS`, render the modal listing incomplete sets per the spec.

### [ ] Verification
- [ ] Manual end-to-end through the UI: register, plan a workout, start it, log sets, finish, see new PBs appear on `/achievements`. Smoke test in Chrome + Firefox.

---

## Phase 8 — Hardening

- [ ] Coverage target: ≥80% lines on `src/UseCase/` and `src/Domain/`. Each UseCase has its own Integration test (M4/M6); repositories are exercised transitively through those. Controllers stay thin (route → resolve UseCase → return DataOutput) and are validated by the cURL smoke checks per phase rather than dedicated test classes.
- [ ] CI (GitHub Actions or GitLab CI): `composer install` → `composer qa` → `frontend/admin` build & typecheck → `frontend/website` build & typecheck. Same pipeline gates the pre-commit hook.
- [ ] Dockerfile prod variant + `compose.prod.yaml` (or hand off to deploy infra).
- [ ] README at repo root with setup instructions (Docker, fixtures, login credentials), pointing at `specifications/conventions.md` as the architectural reference.

---

## Critical files and directories that will be created or touched

- `compose.yaml`, `docker/php/Dockerfile`
- `composer.json` (additions), `phpunit.dist.xml`, `phpstan.dist.neon`, `.php-cs-fixer.dist.php`, `captainhook.json`
- `config/packages/security.yaml`, `config/packages/doctrine.yaml`, `config/packages/lexik_jwt_authentication.yaml`, `config/packages/nelmio_cors.yaml`, `config/packages/nelmio_api_doc.yaml`
- `src/Domain/**`, `src/Infrastructure/**`, `src/UseCase/**` per layout in §0.5
- `src/Domain/Service/PersonalBestEvaluator.php` (M6)
- `migrations/Version*` (one initial)
- `specifications/database-schema.html` (regenerated per schema change)
- `frontend/admin/`, `frontend/website/`
- `tests/Unit/**`, `tests/Integration/**`

## End-to-end verification matrix

| Phase | How to verify | Status |
|---|---|---|
| 0 | `composer qa` green; pre-commit hook fires; `docker compose up` healthy | [x] |
| 1 | Migration applies; `doctrine:schema:validate` clean; PHPStan green | [x] |
| 2 | Fixtures load; repository test asserts eager fetch | [x] |
| 3 | Register → Login → JWT-protected endpoint check via cURL | [~] (admin/player role check deferred to Phase 4) |
| 4 | Integration test per UseCase (15 total: 5 × Equipment/Muscle/Movement); cURL CRUD smoke check for each entity | [x] (OpenAPI annotations deferred) |
| 5 | Manual CRUD in React + AntD admin UI, logged in as the seeded admin (light + dark theme exercised) | [x] |
| ⏸ | Admin path complete — pause | [x] |
| 6 | Integration test per Player UseCase, plus PB-evaluator scenarios cover the full workout lifecycle | [x] (18 use cases + PB evaluator + 4 controllers + lifecycle test + cURL smoke) |
| 7 | Manual UI run-through of the player flows | [ ] |
| 8 | CI green on a clean clone; coverage threshold met | [ ] |
