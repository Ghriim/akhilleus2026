# Akhilleus 2026 — Development Plan

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

- **Coach scope**: deferred entirely. Only `UserDataModel` + `PlayerDataModel` for now.
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

Layout: files at the **root** of `Domain/Gateway/Provider/` and `Domain/Gateway/Persister/` (one file per entity per role, no per-entity sub-folders).

Persister convention: `create` and `update` return the managed `*DataModel` instance (typed narrowly per entity); `delete` returns `void`. `AbstractBaseMysqlPersister` only exposes **protected** helpers `doCreate`/`doUpdate`/`doDelete` typed `DataModelInterface`; each concrete persister in Phase 2+ implements its own public `create`/`update`/`delete` typed per `DataModel` and delegates to those helpers. PHP's variance rules forbid both narrowing a parent's `public create(DataModelInterface)` parameter to `create(MuscleDataModel)` (parameter contravariance) and inheriting the wider `DataModelInterface` return when the gateway requires `MuscleDataModel` (return covariance) — the protected-helper pattern sidesteps both.

- [x] `Provider/MuscleProviderGateway` — `findOneForAdminDetails`, `findAllForAdminList`, `findOneBySlugForUniqueness`.
- [x] `Provider/EquipmentProviderGateway` — same 3 methods.
- [x] `Provider/MovementProviderGateway` — same 3 methods.
- [x] `Persister/MusclePersisterGateway` — `create`, `update`, `delete`.
- [x] `Persister/EquipmentPersisterGateway` — `create`, `update`, `delete`.
- [x] `Persister/MovementPersisterGateway` — `create`, `update`, `delete`.
- [ ] `Provider/UserProviderGateway` + `Persister/UserPersisterGateway` (Phase 3).
- [ ] `Provider/PlayerProviderGateway` + `Persister/PlayerPersisterGateway` (Phase 3).
- [ ] `Provider/WorkoutProviderGateway` + `Persister/WorkoutPersisterGateway` (Phase 6).
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

### [ ] 3.1 User + Player infra
- [ ] `UserRepository` (implements `UserProviderGateway`, plus Symfony `UserLoaderInterface` indirectly via security wiring), `UserPersister`.
- [ ] `PlayerRepository`, `PlayerPersister`.
- [ ] `UserFixtures` — one ROLE_ADMIN seed, one ROLE_PLAYER seed (env-gated).

### [ ] 3.2 Security configuration
- [ ] `config/packages/security.yaml`:
  - [ ] Password hasher: `auto`.
  - [ ] Provider: custom `Infrastructure/Security/UserProvider` backed by `UserProviderGateway`.
  - [ ] Firewalls: `security` (anonymous access for `POST /api/security/registration` and `POST /api/security/login` — Lexik JSON login → JWT), `api` (everything else under `/api`, stateless, JWT bearer).
  - [ ] Access control: `/api/admin/*` requires `ROLE_ADMIN`; `/api/player/*` requires `ROLE_PLAYER`.
- [ ] Lexik JWT bundle: generate keypair (Make target), wire in `lexik_jwt_authentication.yaml`.

### [ ] 3.3 Auth UseCases & Controller
- [ ] `UseCase/Auth/RegisterPlayerUseCase` (extends `AbstractPublicUseCase`):
  - [ ] `RegisterPlayerDataInput` (email, password, displayName) → `RegisterPlayerDataOutput` (player id, email, displayName).
  - [ ] Validator: email format, email unique (calls `UserProviderGateway`), displayName not empty, password meets strength rules — **≥ 8 characters and contains at least one uppercase letter, one lowercase letter, one digit, and one special character**.
  - [ ] Persists `UserDataModel` (with `ROLE_PLAYER`) then `PlayerDataModel`.
- [ ] Login is handled entirely by Lexik's JSON login authenticator — no UseCase needed.
- [ ] `Infrastructure/Controller/Security/SecurityController`:
  - [ ] `POST /api/security/registration` — calls `RegisterPlayerUseCase`, returns 201 with the created player.
  - [ ] `POST /api/security/login` — handled by Lexik's JSON login authenticator (controller route exists only to anchor the firewall pattern; body is empty).
  - [ ] `POST /api/security/logout` — stateless JWTs mean the client just discards its token; this endpoint returns `204 No Content` and acts as a logout signal for clients/audit logs. If true server-side invalidation becomes a requirement later, swap in a JWT blocklist (e.g. Redis-backed) without changing the URL.

### [ ] 3.4 Verification
- [ ] `curl POST /api/security/registration` creates a user; subsequent `POST /api/security/login` returns a JWT; `GET /api/admin/me` with that JWT (admin seed) returns 200, with player JWT returns 403; `POST /api/security/logout` returns 204.

---

## Phase 4 — Admin REST API (Equipment, Muscle, Movement CRUD)

For each of the three entities, five UseCases under `UseCase/Admin/{Entity}/`:

- [ ] `Create{Entity}UseCase` (extends `AbstractLoggedUserUseCase`)
- [ ] `Update{Entity}UseCase`
- [ ] `Delete{Entity}UseCase`
- [ ] `List{Entity}sUseCase`
- [ ] `Get{Entity}DetailsUseCase`

DTOs (`Domain/DTO/...`):
- [ ] `DataInput/Admin/{Entity}/Create{Entity}DataInput`
- [ ] `DataInput/Admin/{Entity}/Update{Entity}DataInput`
- [ ] `DataInput/Admin/{Entity}/Delete{Entity}DataInput` (just an id wrapper, kept for consistency)
- [ ] `DataInput/Admin/{Entity}/Get{Entity}DetailsDataInput`
- [ ] `DataOutput/Admin/{Entity}/{Entity}DataOutput`
- [ ] `DataOutput/Admin/{Entity}/{Entity}ListItemDataOutput` (lightweight summary form for list endpoints)

Validators (`Domain/Validator/Admin/{Entity}/`):
- [ ] `Create{Entity}Validator`, `Update{Entity}Validator`, `Delete{Entity}Validator`
- [ ] Movement-specific rules: at least one tracking field true; mainMuscle exists; secondaryMuscles all exist; equipments all exist; slug unique.

Controllers (`Infrastructure/Controller/Admin/`):
- [ ] `EquipmentAdminController`, `MuscleAdminController`, `MovementAdminController` — each with the standard 5 routes:
  - [ ] `GET    /api/admin/{plural}`
  - [ ] `GET    /api/admin/{plural}/{id}`
  - [ ] `POST   /api/admin/{plural}`
  - [ ] `PUT    /api/admin/{plural}/{id}`
  - [ ] `DELETE /api/admin/{plural}/{id}`

Cross-cutting:
- [ ] A small `Infrastructure/Controller/ExceptionListener` (or kernel.exception subscriber) translates `EntityNotFoundException` → 404, `ValidationException` → 422 with structured errors, `UnauthorizedException` → 401/403.
- [ ] OpenAPI annotations on each controller (NelmioApiDoc) — exposed at `/api/doc.json` for the React Admin data provider to consume.

### [ ] Verification
- [ ] **One Integration test class per UseCase** (not per Controller). Each test resolves the UseCase from the container, calls `execute()` against a real (transactional) MySQL DB via `dama/doctrine-test-bundle`, and covers happy path + validation errors + not-found / unauthorized cases. Controllers themselves are thin and verified via cURL smoke checks rather than dedicated test classes.
- [ ] Manual cURL flow for each entity: list → create → get → update → delete (covers HTTP wiring, serialization, exception listener mapping to 404/422/401/403).

---

## Phase 5 — Admin frontend (React Admin TS)

- [ ] Bootstrap `frontend/admin/` with Vite + React 18 + TypeScript + `react-admin` v5.
- [ ] Auth provider posts to `/api/security/login`, stores JWT in localStorage, attaches `Authorization: Bearer …` to all data-provider requests, calls `POST /api/security/logout` and clears storage on user logout or 401.
- [ ] Data provider: `ra-data-simple-rest` against `/api/admin`.
- [ ] Resources:
  - [ ] `equipments` — List(slug, label), Edit, Create, Show.
  - [ ] `muscles` — List(slug, label), Edit, Create.
  - [ ] `movements` — List(label, mainMuscle, equipment count), Edit/Create with: slug, label, AutocompleteInput on mainMuscle, AutocompleteArrayInput on secondaryMuscles, AutocompleteArrayInput on equipments, six BooleanInput fields for tracking flags.
- [ ] Theme: simple Material UI default — D&D theming is reserved for the player site per the spec. Plain admin look reduces noise.
- [ ] Add npm scripts: `dev`, `build`, `lint`, `typecheck`. CI gate: `typecheck` + `build`.

### [ ] Verification
- [ ] `npm run dev` from `frontend/admin/` opens the admin; log in with the admin seed; CRUD all three resources end-to-end.

---

## ⏸ Checkpoint — admin path complete

At this point an admin can log in and manage Equipment / Muscle / Movement reference data through the React Admin UI, backed by a tested REST API and a fixture-seeded MySQL database. **Pause here** — the player website work is a separate stage.

---

## Phase 6 — Player REST API

### [ ] 6.1 Workout creation & lifecycle
UseCases under `UseCase/Player/Workout/`:
- [ ] `StartEmptyWorkoutUseCase` — creates `WorkoutDataModel` with `status=IN_PROGRESS`, `dateStart=now`.
- [ ] `PlanWorkoutUseCase` — creates `WorkoutDataModel` with `status=PLANNED`, `plannedAt=$input->plannedAt`.
- [ ] `StartPlannedWorkoutUseCase` — transitions PLANNED → IN_PROGRESS, sets `dateStart=now`.
- [ ] `CancelWorkoutUseCase` — IN_PROGRESS or PLANNED → CANCELED.
- [ ] `FinishWorkoutUseCase` — see §6.3.

### [ ] 6.2 Workout content
UseCases under `UseCase/Player/Exercise/` and `UseCase/Player/ExerciseSet/`:
- [ ] `AddMovementToWorkoutUseCase`
- [ ] `RemoveMovementFromWorkoutUseCase`
- [ ] `UpdateMovementRestDurationUseCase`
- [ ] `ReorderMovementsUseCase`
- [ ] `AddExerciseSetUseCase` (planned values)
- [ ] `UpdateExerciseSetPlannedUseCase`
- [ ] `UpdateExerciseSetAchievedUseCase`
- [ ] `RemoveExerciseSetUseCase`
- [ ] `MarkExerciseSetCompletedUseCase`

### [ ] 6.3 Finishing a workout & personal bests
- [ ] `FinishWorkoutUseCase`:
  1. Reload workout with all sets eager-fetched.
  2. If any set is not `completed`, throw `ValidationException` with code `WORKOUT_HAS_INCOMPLETE_SETS` and a list of incomplete set ids — frontend renders the spec's modal from this payload.
  3. Set `dateEnd=now`, `status=COMPLETED`, persist.
  4. Delegate to `Domain/Service/PersonalBestEvaluator` (pure domain code) which receives the workout + a `PersonalBestProviderGateway` and returns a list of `PersonalBestDataModel` upserts; the use case persists them via `PersonalBestPersisterGateway`.
- [ ] `PersonalBestEvaluator` computes the seven categories from the spec:
  - [ ] HIGHEST_WEIGHT — `max(achievedWeight)` per movement.
  - [ ] HIGHEST_REPS — `max(achievedReps)`.
  - [ ] HIGHEST_VOLUME_ONE_SET — `max(achievedReps * achievedWeight)`.
  - [ ] HIGHEST_VOLUME_WORKOUT — `sum(achievedReps * achievedWeight)` for that movement in that workout.
  - [ ] HIGHEST_DURATION — `max(achievedDurationSeconds)`.
  - [ ] HIGHEST_DISTANCE — `max(achievedDistanceMeters)`.
  - [ ] HIGHEST_SPEED — `max(achievedDistanceMeters / achievedDurationSeconds)` *(confirm formula — the spec text "duration * distance" appears to be a typo)*.
- [ ] A category only fires if the underlying movement tracks the relevant fields (e.g. HIGHEST_REPS only on movements where `tracksRepetitions=true`).

### [ ] 6.4 Read endpoints
UseCases under `UseCase/Player/Read/`:
- [ ] `ListWorkoutHistoryUseCase` — completed workouts for the logged player, paginated, ordered by `dateEnd DESC`.
- [ ] `ListUpcomingWorkoutsUseCase` — `status=PLANNED OR IN_PROGRESS`, ordered by `plannedAt ASC NULLS LAST`.
- [ ] `GetWorkoutDetailsUseCase` — full hydrate (movements + sets), used by both history and live workout views.
- [ ] `ListPersonalBestsUseCase` — grouped by movement, returns one `PlayerMovementPersonalBestsDataOutput` per movement that has any PB.

### [ ] 6.5 Controllers
Under `Infrastructure/Controller/Player/`:
- [ ] `WorkoutPlayerController` — start/plan/start-planned/cancel/finish/list/details.
- [ ] `ExercisePlayerController` — add/remove/reorder/update rest.
- [ ] `ExerciseSetPlayerController` — add/update planned/update achieved/remove/mark-complete.
- [ ] `PersonalBestPlayerController` — list.

All routes under `/api/player/*`, `ROLE_PLAYER` required. The `AbstractLoggedUserValidator` resolves the current `PlayerDataModel` once per request and is injected into every player UseCase.

### [ ] 6.6 Verification
- [ ] Per-UseCase Integration tests covering: full happy path (StartEmptyWorkout → AddMovementToWorkout → AddExerciseSet → MarkExerciseSetCompleted → FinishWorkout → assert PBs persisted); `FinishWorkoutUseCase` throws `ValidationException` with code `WORKOUT_HAS_INCOMPLETE_SETS` when sets remain incomplete; a second workout that ties a previous best does not create a duplicate PB; a third that beats it updates the value.

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
| 3 | Register → Login → JWT-protected endpoint check via cURL | [ ] |
| 4 | Integration test per UseCase (15 total: 5 × Equipment/Muscle/Movement); cURL CRUD smoke check for each entity | [ ] |
| 5 | Manual CRUD in React Admin UI, logged in as the seeded admin | [ ] |
| ⏸ | Admin path complete — pause | [ ] |
| 6 | Integration test per Player UseCase, plus PB-evaluator scenarios cover the full workout lifecycle | [ ] |
| 7 | Manual UI run-through of the player flows | [ ] |
| 8 | CI green on a clean clone; coverage threshold met | [ ] |
