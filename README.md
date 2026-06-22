# Akhilleus 2026

Gamified, RPG-styled training-tracking app. Stack:

- **Backend**: Symfony 8 / PHP 8.4, MySQL 8.4, JWT auth (Lexik), strict Domain / Infrastructure / UseCase split.
- **Frontend admin**: React 18 + TypeScript + Vite + Ant Design, used by admins to manage Equipment / Muscle / Movement reference data.
- **Frontend website**: React 18 + TypeScript + Vite, plain CSS with a D&D / medieval-fantasy palette, used by players to plan / run / review workouts.

The full product spec, the architectural conventions, and the implementation roadmap live under [`specifications/`](specifications/) — read those before designing or implementing anything new.

| Document                                                       | What it is                                                                                          |
|----------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| [`specifications/conventions.md`](specifications/conventions.md)                   | Non-negotiable coding rules (final classes, strict types, Yoda, suffix rules, DTO categories, Repository/Persister pattern, UseCase contract, boolean naming). Version-agnostic. |
| [`specifications/v1/dev-plan.md`](specifications/v1/dev-plan.md)                   | **Active roadmap** with `[x]` / `[ ]` / `[~]` checkboxes + a "Decisions / deviations" block. Source of truth for "what's done" / "what's next". |
| [`specifications/v1/initial-requirements.md`](specifications/v1/initial-requirements.md) | Frozen v1 product spec (do not edit). |
| [`specifications/v0/`](specifications/v0/)                                          | Archived MVP requirements + dev-plan — historical context for the inherited codebase. |

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

# 6. Load the dev fixtures (admin + player accounts, muscles, equipments, movements,
#    the LevelingConfig singleton, the level-curve brackets, and the quest seeds).
php bin/console doctrine:fixtures:load --no-interaction

# 7. Start the API (HTTPS, https://127.0.0.1:8000).
symfony server:start -d
```

> **Test DB troubleshooting** — if the integration suite suddenly errors with
> `SQLSTATE[HY000] [1044] Access denied for user 'app'@'%' to database 'akhilleus_test'`,
> the test schema or its grants got dropped (most often after a `docker compose down -v`).
> Re-run `composer setup:test-db` followed by `APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction` to recover.

### Daily startup

Once the one-shot setup above is done, subsequent sessions can boot everything in one go:

```bash
composer dev:up
```

This brings up the database + both frontend containers (waits on the MySQL healthcheck), generates the JWT keypair if it is missing, applies any pending migrations on the dev DB, and starts the Symfony API in the background. The script prints the three local URLs (API / admin / player site) at the end. Idempotent — safe to re-run anytime. **Does not** load fixtures (would truncate any test data you have entered manually); when you need a clean re-seed, run `php bin/console doctrine:fixtures:load --no-interaction` on its own.

Seeded credentials:

| Role          | Email                    | Password       |
|---------------|--------------------------|----------------|
| `ROLE_ADMIN`  | `admin@akhilleus.test`   | `AdminAdmin1!` |
| `ROLE_PLAYER` | `player@akhilleus.test`  | `PlayerHero1!` |

Beyond the two accounts, the fixtures seed the muscle / equipment / movement catalogue plus the v1 data: the **`LevelingConfig` singleton** (`xpPerWorkoutMinute`), the **`LevelBracket` rows** that define the XP curve, and the **quest seeds**. The `LevelingConfig` row and the level brackets are also created by migrations (`Version20260613130000` and the bracket data migration), so a migrated-but-unseeded DB still has a usable leveling baseline.

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

## Scheduled jobs (cron)

The nightly **leveling lock** folds each player's still-unlocked XP grants (workout completions,
claimed quest rewards) earned before today into their level/XP, then locks those grants so they are
never counted twice:

```bash
php bin/console app:leveling:lock-yesterday
# --cutoff=2026-06-17T00:00:00+02:00   # debug/testing override; defaults to today 00:00 Europe/Paris
```

It is idempotent (a second run finds nothing left to lock) and timezone-anchored to Europe/Paris.

**Production wiring** — Symfony Scheduler is *not* installed (a single nightly job doesn't warrant
Messenger + a worker process). Schedule it from the host crontab at 01:00 local time (a 1-hour buffer
past midnight for clock skew):

```cron
# /etc/cron.d/akhilleus  (or `crontab -e` for the deploy user)
0 1 * * * www-data cd /var/www/akhilleus && /usr/bin/php bin/console app:leveling:lock-yesterday --env=prod >> var/log/cron.log 2>&1
```

Ensure the host timezone is `Europe/Paris` (or pass `TZ=Europe/Paris` in the crontab line) so `0 1`
fires at 01:00 Paris. If a future deploy adopts a different orchestrator (systemd timer, Kubernetes
CronJob, supervisor), point it at the same command — the command owns all the logic and the timezone.

## Architecture overview

The strict layering is enforced by `specifications/conventions.md`:

- **`src/Domain/`** — pure business code (DTOs, gateways, registries, validators, services, exceptions). Cannot import anything outside `Domain` except a small whitelist (`Doctrine\ORM\Mapping`, `Doctrine\DBAL\Types\Types`, `Doctrine\Common\Collections`, `Symfony\Component\Security` only inside `UserDataModel`, `\Exception` only in `Domain/Exception`).
- **`src/Infrastructure/`** — adapters: Doctrine repositories implementing the `*ProviderGateway` interfaces, Persisters implementing the `*PersisterGateway` interfaces, Symfony controllers, security wiring, fixtures.
- **`src/UseCase/`** — `final` classes implementing `UseCaseInterface`. Single `execute(DataInputInterface): DataOutputInterface|list<DataOutputInterface>`. Three abstract bases: `AbstractPublicUseCase` (no auth), `AbstractLoggedAdminUseCase` (admin), `AbstractLoggedPlayerUseCase` (player). Only Controllers and Commands may reference `UseCase`.

The domain is organised into sub-domains under each layer. Beyond the v0 core (Training: movements, workouts, exercises, sets, personal bests), v1 added:

- **Tracking** — per-day player metrics (steps, hydration, sleep, weight) with their own DataModels, gateways, player UseCases, REST endpoints, and a dashboard widget.
- **Leveling** — the XP economy: `LevelBracket` (the curve, admin-managed), the `LevelingConfig` singleton (`xpPerWorkoutMinute`), and `EarnedExperience` (the per-player XP journal). Workout completions and claimed quest rewards mint `EarnedExperience`; the nightly cron (see [Scheduled jobs](#scheduled-jobs-cron)) folds unlocked grants into the player's level/XP and locks them.
- **Questing** — admin-authored `Quest`s (daily / weekly / monthly / unique, AUTOMATIC or MANUAL) and per-player `QuestProgression`. AUTOMATIC quests auto-progress off tracking metrics and become claimable for an `EarnedExperience` reward.

Every UseCase has its own integration test under `tests/Integration/UseCase/...`; every Validator has its own unit test under `tests/Unit/Domain/Validator/...`.

Database schema diagram: [`specifications/database-schema.html`](specifications/database-schema.html) — regenerated whenever the schema changes.

## License

Personal project — no public license set yet.
