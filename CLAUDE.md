# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
A complete plan for this project as been written in @specifications/dev-plan.md.

## Project status (snapshot — verify against `specifications/dev-plan.md` checkboxes)

The project is mid-build, executing the plan in `specifications/dev-plan.md`. **Always start a session by reading `specifications/dev-plan.md` to see which subsections are checked off (`[x]`) and which are next (`[ ]`)** — it is the source of truth, this section is just a quick orientation.

As of the last session:
- **Phases 0 → 6 are complete** (foundation, entities, admin REST API + frontend, full Player REST API: workout lifecycle / content / finish + personal bests / read endpoints, with `PlayerWorkoutLifecycleTest` + cURL smoke covering all 13 Phase-6 endpoints). The ⏸ admin/player checkpoint is passed.
- **Phase 7.1 → 7.8 are implemented on disk** (player website: auth, dashboard / upcoming / history / new / details / achievements pages, status-conditional details routing — Planned / Live / Read-only — Finish flow with `WORKOUT_HAS_INCOMPLETE_SETS` modal + newPersonalBests display, full D&D theming with parchment/ink/gold + Cinzel + Lora). Two backend additions landed alongside Phase 7: registration moved from `SecurityController` to a dedicated `App\Infrastructure\Controller\User\PlayerController` (`POST /api/player/registration`), and a "single in-progress workout per player" rule (`StartEmptyWorkoutValidator::ALREADY_IN_PROGRESS_CODE` + `WorkoutProviderGateway::findInProgressByPlayer`).
- **Phase 7 verification is `[~]`**: build + typecheck + lint pass clean on `frontend/website`; the manual Chrome/Firefox UI smoke run-through is still pending. There's also uncommitted polish on `frontend/website/{index.html, src/components/WorkoutListItem.tsx, src/index.css}`.
- **Next pending step**: finish Phase 7 verification (manual smoke + commit the working-tree polish), then Phase 8 (hardening — coverage target ≥ 80% on UseCase/Domain, CI pipeline, prod Docker variant, README).

`composer qa` last known green: cs ✅, stan ✅, phpunit ✅ (209 tests / 440 assertions, snapshot taken at the close of Phase 6.6).

When picking up work, **never rebuild what's already in place** — always check the on-disk reality first (`composer.json`, `src/`, `config/packages/`, `frontend/website/src/`, the dev-plan checkboxes) before scaffolding. The dev-plan's "Decisions / deviations" section + Phase 6.1's "Foundations introduced in 6.1" bullet list + the new "Phase 7-era backend additions" block are the authoritative summary of conventions that apply to all subsequent work (esp. the Admin/Player abstract split, the date-as-ISO-8601-string DataOutput convention, the controller-per-batch landing strategy, and the moved registration endpoint).

## Working mode

Implementation work proceeds **step-by-step**, where each "step" is one numbered subsection of `specifications/dev-plan.md` (e.g. `0.1`, `0.2`, …, `1.1`, `1.2`, …, `6.2`, `6.3`, …). After completing a step:

1. Run `composer qa` (or the relevant subset) to confirm green.
2. Update `specifications/dev-plan.md` — flip `[ ]` to `[x]` for everything genuinely done in that step. Keep `[~]` for partially-done steps where some leaf bullets remain pending in a later sub-step (e.g. the controllers landed per batch land in `6.5` but the work itself is in 6.x).
3. **Pause** and summarize what was done and what design choices were made (especially anything that deviates from `conventions.md` — flag those clearly so the user can roll back).
4. Wait for the user to say "next" / "go" / similar before starting the next step.

Do not commit, push, or chain multiple steps without explicit user confirmation. If you encounter a decision that materially deviates from `conventions.md` or the dev-plan, raise it for review before applying — don't silently take the deviation. When you do take one, **append a note to `specifications/dev-plan.md`'s "Decisions / deviations" block** so the next session inherits the working contract.

The user works in French; responses can be in French.

## Authoritative specifications

Three files in `specifications/` are the source of truth — read them before designing or implementing anything:

- **`specifications/conventions.md`** — non-negotiable coding rules (final classes, `declare(strict_types=1)`, Yoda conditions, class suffixes, Domain isolation, DTO categories, Repository/Persister + Gateway pattern, UseCase contract). Apply these to every PHP file you write.
- **`specifications/initial-requirements.md`** — product scope: a gamified (RPG-style) training-tracking app with an admin (React + TS + AntD), a player+coach website (React TS), and a REST API with JWT auth. Defines the domain entities (Equipment, Muscle, Movement, Workout, ExerciseSet, personal bests…) and the muscle fixture seed list.
- **`specifications/dev-plan.md`** — the executable roadmap with `[x]` / `[ ]` / `[~]` checkboxes per subsection and per leaf bullet. Source of truth for "what's done" and "what's next."

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
  - `Infrastructure/DataFixtures/` — Symfony FixtureBundle fixtures (the muscle list in `initial-requirements.md` is a fixture seed). Fixtures **must inject the matching `*PersisterGateway` and call `create(...)`** — they never set timestamps or call `EntityManager::persist/flush` directly.
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
