# Akhilleus 2026

Gamified, RPG-styled training-tracking app. Stack:

- **Backend**: Symfony 8 / PHP 8.4, MySQL 8.4, JWT auth (Lexik), strict Domain / Infrastructure / UseCase split.
- **Frontend admin**: React 18 + TypeScript + Vite + Ant Design, used by admins to manage Equipment / Muscle / Movement reference data.
- **Frontend website**: React 18 + TypeScript + Vite, plain CSS with a D&D / medieval-fantasy palette, used by players to plan / run / review workouts.

The full product spec, the architectural conventions, and the implementation roadmap live under [`specifications/`](specifications/) — read those before designing or implementing anything new.

| Document                                                       | What it is                                                                                          |
|----------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| [`specifications/initial-requirements.md`](specifications/v0/initial-requirements.md) | Frozen product spec (do not edit). Defines the domain entities, the muscle seed list, the player flows. |
| [`specifications/conventions.md`](specifications/conventions.md)                   | Non-negotiable coding rules (final classes, strict types, Yoda, suffix rules, DTO categories, Repository/Persister pattern, UseCase contract, boolean naming). |
| [`specifications/dev-plan.md`](specifications/v0/dev-plan.md)                         | Executable roadmap with `[x]` / `[ ]` / `[~]` checkboxes. Source of truth for "what's done" / "what's next". |

## Setup

Prerequisites: Docker (with compose v2), [Symfony CLI](https://symfony.com/download), Node 22 (only if you want to run the frontends outside of Docker).

```bash
git clone git@github.com:Ghriim/akhilleus2026.git
cd akhilleus2026

# 1. Boot the MySQL service (and optionally the frontend dev servers).
docker compose up -d database

# 2. Install PHP dependencies.
composer install

# 3. Generate the JWT keypair (passphrase: see JWT_PASSPHRASE in .env).
php bin/console lexik:jwt:generate-keypair

# 4. Provision the test database (creates `akhilleus_test` and grants the
#    `app` user access to `akhilleus_test%`). Idempotent — also re-runnable
#    after `docker compose down -v` or any MySQL volume reset.
composer setup:test-db

# 5. Run the migrations on dev + test databases.
php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction

# 6. Load the dev fixtures (admin + player accounts, muscles, equipments, movements).
php bin/console doctrine:fixtures:load --no-interaction

# 7. Start the API (HTTPS, https://127.0.0.1:8000).
symfony server:start -d
```

> **Test DB troubleshooting** — if the integration suite suddenly errors with
> `SQLSTATE[HY000] [1044] Access denied for user 'app'@'%' to database 'akhilleus_test'`,
> the test schema or its grants got dropped (most often after a `docker compose down -v`).
> Re-run `composer setup:test-db` followed by `APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction` to recover.

Seeded credentials:

| Role          | Email                    | Password       |
|---------------|--------------------------|----------------|
| `ROLE_ADMIN`  | `admin@akhilleus.test`   | `AdminAdmin1!` |
| `ROLE_PLAYER` | `player@akhilleus.test`  | `PlayerHero1!` |

### Frontends

Both frontend apps run inside their own Docker container (Node 22) on the host network:

```bash
# Admin app — http://localhost:5173
docker compose up -d frontend-admin

# Player website — http://localhost:5174
docker compose up -d frontend-website
```

The Vite dev servers reach the API at `https://127.0.0.1:8000` by default (override via `VITE_API_BASE_URL`). Run `symfony server:ca:install` once so the browser trusts the local TLS certificate.

## Development workflow

Implementation work proceeds **step-by-step**, where each step is one numbered subsection of `specifications/dev-plan.md`. After each step:

1. Run `composer qa` (cs + stan + phpunit) to confirm green.
2. Update `specifications/dev-plan.md` — flip `[ ]` to `[x]` for everything genuinely done.
3. Note any deviation from `conventions.md` in the dev-plan's "Decisions / deviations" block.
4. Pause and let the user confirm before starting the next step.

A pre-commit hook (captainhook) runs PHP-CS-Fixer + PHPStan + the Unit suite on every commit. Don't bypass it — fix the issue or update tests.

## Quality gates

```bash
# All-in-one (what the CI runs)
composer qa

# Individual targets
composer cs              # PHP-CS-Fixer dry-run
composer cs:fix          # PHP-CS-Fixer auto-fix
composer stan            # PHPStan level 8
composer test            # Full PHPUnit suite (Unit + Integration)
composer test:unit       # Unit suite only (what the pre-commit hook runs)
composer test:integration # Integration suite (needs MySQL up)
composer setup:test-db    # Re-provision the test DB + grants if they were dropped

# Single test class
vendor/bin/phpunit --filter SomeTest
```

Frontend (run inside the container or with a local Node 22):

```bash
docker compose exec -T frontend-admin   sh -c "npm run typecheck && npm run lint && npm run build"
docker compose exec -T frontend-website sh -c "npm run typecheck && npm run lint && npm run build"
```

## Architecture overview

The strict layering is enforced by `specifications/conventions.md`:

- **`src/Domain/`** — pure business code (DTOs, gateways, registries, validators, services, exceptions). Cannot import anything outside `Domain` except a small whitelist (`Doctrine\ORM\Mapping`, `Doctrine\DBAL\Types\Types`, `Doctrine\Common\Collections`, `Symfony\Component\Security` only inside `UserDataModel`, `\Exception` only in `Domain/Exception`).
- **`src/Infrastructure/`** — adapters: Doctrine repositories implementing the `*ProviderGateway` interfaces, Persisters implementing the `*PersisterGateway` interfaces, Symfony controllers, security wiring, fixtures.
- **`src/UseCase/`** — `final` classes implementing `UseCaseInterface`. Single `execute(DataInputInterface): DataOutputInterface|list<DataOutputInterface>`. Three abstract bases: `AbstractPublicUseCase` (no auth), `AbstractLoggedAdminUseCase` (admin), `AbstractLoggedPlayerUseCase` (player). Only Controllers and Commands may reference `UseCase`.

Every UseCase has its own integration test under `tests/Integration/UseCase/...`; every Validator has its own unit test under `tests/Unit/Domain/Validator/...`.

Database schema diagram: [`specifications/database-schema.html`](specifications/database-schema.html) — regenerated whenever the schema changes.

## License

Personal project — no public license set yet.
