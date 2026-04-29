# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
A complete plan for this project as been written in @dev-plan.md
## Project status

This is a **greenfield Symfony 8 / PHP 8.4 skeleton**. As of now `src/` only contains `Kernel.php` and an empty `Controller/` directory — none of the domain code described in the specs has been implemented yet. When asked to build a feature, you are expected to scaffold it from scratch following the conventions below.

No fixtures bundle, ORM, or auth layer is installed yet (`composer.json` only requires `symfony/console`, `symfony/dotenv`, `symfony/flex`, `symfony/framework-bundle`, `symfony/runtime`, `symfony/yaml`). When work requires Doctrine, JWT auth, the FixtureBundle, etc., add the dependency rather than assuming it exists. The quality toolchain (PHPUnit, PHPStan, PHP-CS-Fixer) is the project standard — see **Commands** below.

## Authoritative specifications

Two files in `specifications/` are the source of truth — read them before designing or implementing anything:

- **`specifications/conventions.md`** — non-negotiable coding rules (final classes, `declare(strict_types=1)`, Yoda conditions, class suffixes, Domain isolation, DTO categories, Repository/Persister + Gateway pattern, UseCase contract). Apply these to every PHP file you write.
- **`specifications/initial-requirements.md`** — product scope: a gamified (RPG-style) training-tracking app with an admin (React Admin / TS), a player+coach website (React TS), and a REST API with JWT auth. Defines the domain entities (Equipment, Muscle, Movement, Workout, ExerciseSet, personal bests…) and the muscle fixture seed list.

Per `conventions.md`, whenever the database schema changes you must regenerate `specifications/database-schema.html` (HTML5 schema diagram).

## Architecture (target shape)

The conventions impose a strict **Domain / Infrastructure** split that does not yet exist on disk but must be created under `src/`:

- **`Domain/`** — pure business code. Cannot import anything from outside `Domain` *except* the four documented exceptions: `Doctrine\DBAL\Types\Types` and `Doctrine\ORM\Mapping as ORM` only inside `Domain/DTO/DataModel/{SubDomain}`; `Symfony\Component\Security\…` only inside `UserDataModel`; `\Exception` only inside `Domain/Exception`.
  - `Domain/DTO/DataInput|DataOutput|DataModel/` — three DTO flavors, public properties, no getters/setters, each implements its DTO interface. `DataModel` = Doctrine entity (suffix `DataModel`, requires `createdAt`/`updatedAt` handled by the persister).
  - `Domain/Gateway/Provider/` and `Domain/Gateway/Persister/` — interfaces injected in place of concrete repositories/persisters (1-to-1 mapping, no services.yaml wiring needed). Naming: `WorkoutProviderGateway`, `WorkoutPersisterGateway`.
  - `Domain/Registry/{Entity}/` — interfaces holding constants tied to a DTO (e.g. `WorkoutStatusRegistry`).
- **`Infrastructure/`** — adapters.
  - `Infrastructure/Repository/` — implements a `Provider` gateway. **Never** call generic Doctrine finders (`find`, `findOneBy`, …); write context-named methods like `findOneForWorkoutDetails`. Never rely on lazy-loading.
  - `Infrastructure/Persister/` — extends `AbstractBaseMysqlPersister`, implements a `Persister` gateway, owns `createdAt`/`updatedAt` via `ClockInterface`, and is where post-create/update/delete side effects live.
  - `Infrastructure/DataFixtures/` — Symfony FixtureBundle fixtures (the muscle list in `initial-requirements.md` is a fixture seed).
  - `Infrastructure/Controller/` — thin HTTP entry points; together with Commands they are the only callers allowed to reach into `UseCase`.
- **`UseCase/`** — `final` classes with a single `execute()` method, take a `DataInputInterface`, return `DataOutputInterface` (or array of). Extend `AbstractPublicUseCase` (injects `DomainValidatorInterface`) or `AbstractLoggedUserUseCase` (injects `AbstractLoggedUserValidator`). Only Controllers and Commands may reference `UseCase`.

Class-name suffix rules (from `conventions.md`): `DataModel`, `Repository`, `UseCase`, `Validator`. All classes are `final` by default (DataModels excepted) and `readonly` when feasible.

Theming note from the requirements: the player website is D&D-flavored / medieval-fantasy — colors must be CSS variables.

## Commands

PHP / Symfony:

```bash
composer install                  # install deps; auto-runs cache:clear and assets:install
php bin/console                   # list all Symfony console commands
php bin/console cache:clear
symfony server:start -d           # start the local web server in the background (use `symfony serve` for foreground)
symfony server:stop
```

The local dev server uses the Symfony CLI (`symfony` binary) — it is the supported way to serve the app, not the built-in PHP server.

Quality toolchain (run before committing — all three are also enforced by a **pre-commit git hook**, so a commit that fails any of them will be rejected):

```bash
vendor/bin/phpunit                          # full test suite
vendor/bin/phpunit --filter SomeTest        # run a single test class or method
vendor/bin/phpstan analyse                  # static analysis (config: phpstan.neon / phpstan.dist.neon)
vendor/bin/php-cs-fixer fix                 # auto-format
vendor/bin/php-cs-fixer fix --dry-run --diff   # check formatting without writing
```

Never bypass the hook with `--no-verify`; if it fails, fix the underlying issue (missing strict_types declaration, non-Yoda condition, type error, failing test, …) before re-committing.

## Environment

`.env` ships with `APP_ENV=dev`, `APP_SHARE_DIR=var/share`, and `DEFAULT_URI=http://localhost`. `.env.dev` sets a dev `APP_SECRET`. Local overrides go in `.env.local` (gitignored). PHP 8.4 is required (`composer.json`), and Symfony is pinned to `8.0.*` via the `extra.symfony.require` constraint — keep new Symfony packages on the same minor.
