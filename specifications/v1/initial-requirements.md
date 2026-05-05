## Introduction

I want to improve my existing application to add a gamification aspect as well as tracking some information helpful to evaluate my fitness level.
All the pre-defined conventions are still to be used and can be found in @specifications/conventions.md

## Improving exiting functionalities

### Admin

### Movement

A movement gains two new properties:
- `videoLink` (nullable string) — URL of an external video demonstrating the movement.
- `gifLink` (nullable string) — URL of an external GIF showing the movement.

Both are stored as URLs (no upload / local storage in v1) and may be displayed in the Player's workout view to help them execute the movement correctly.

### Training

The introduction of the Leveling sub-domain forces a few changes on the existing Training sub-domain (full details in `Cron job → Leveling` further below):
- Workout deletion is restricted to the same day. Outside that window, the workout transitions to a new `deleted` status (added to `WorkoutStatusRegistry`) and is filtered out of regular listings.
- Same-day workout edits / deletes propagate to the matching `EarnedExperience`. Past-day edits do not.
- Retroactive workout creation (completion datetime in the past) is allowed but earns no XP.

### Player

We will add a website area (menu in the header) called **Statistiques** where the user will be able to see charts of his activity. The exact content is not decided yet and will be defined in a later iteration once the rest of v1 is in place. **For v1, only the menu entry and an empty placeholder page are delivered**; chart content is out of scope.

## Description of the new functionalities

### Player

We will introduce three new sub-domains (parallel to the existing `Training` sub-domain):
- `Tracking` — daily metrics logged by the Player (steps, hydration, sleep, weight).
- `Leveling` — XP, levels, and the journal of earned experience.
- `Questing` — quests (missions/tasks) and per-period progression.

#### Tracking

We will allow the Player to track a few metrics:
- Steps: `StepsDailyEntry` with `date` (date, non-null), `count` (int ≥ 0, non-null). Single editable value per day; uniqueness on (`player`, `date`).
- Hydration:
  - The Player has a global `dailyHydrationTargetMl` (int, non-null, mL) configured on its profile, **defaulting to 1000 mL** for newly created players.
  - `HydrationDailySummary`: per-day record with `date`, `targetMl` (int, non-null) and `amountConsumedMl`.
    - At creation, `targetMl` is **snapshotted from the Player's current `dailyHydrationTargetMl`**. The Player can subsequently edit it for that specific day without affecting the global setting.
    - **`amountConsumedMl` is auto-derived** as the sum of the day's `HydrationEntry.valueMl`, recomputed in a `HydrationAggregateEvaluator` (mirroring the `WorkoutAggregateEvaluator` pattern) on every entry create / update / delete.
    - **Lazy creation:** a Summary for date D is auto-created the first time it is needed for date D — namely, on the first read of the day's tracking widget. Until then, no row exists for that date.
  - `HydrationEntry`: per-event record with `loggedAt` (datetime) and `valueMl` (int, mL). Creating an entry for a date with no Summary yet must also auto-create the Summary on the fly (same snapshot rule).
  - **Future evolution (out of v1 scope):** the daily target may auto-adjust based on the Player's workout activity (e.g. +X mL after Y minutes of cardio).
- Sleep: `SleepDailyEntry`, **one entry per night** (naps are out of scope). Properties:
  - `date` (date, non-null) — the **wake-up date** (e.g. a sleep from May 5 23:00 to May 6 07:00 belongs to May 6).
  - `bedAt` (datetime, non-null) — when the Player went to bed.
  - `wakeAt` (datetime, non-null) — when the Player woke up.
  - `durationMinutes` (int, non-null, **auto-derived** from `bedAt` / `wakeAt` via a domain service mirroring the `WorkoutAggregateEvaluator` pattern).
  - `quality` (nullable int, range `[1, 5]`).
  - Uniqueness: one entry per (`player`, `date`).
- Weight: `WeightEntry` with `loggedAt` (datetime, non-null — keeping the full timestamp opens the door to later analysis on the impact of the time-of-day on the measurement), `valueGrams` (int > 0, non-null), and a stored `date` (date, non-null, auto-derived from `loggedAt` at persist time, used to enforce uniqueness on (`player`, `date`) — a single weight entry per day). This data feeds the progression graph.

A widget will be added on the Player dashboard to view/edit the current day tracked information.

### Leveling

This is the core of the gamification process.
A Player will now have **three stored columns** on `PlayerDataModel`, mutated only by the nightly cron (see `Cron job → Leveling`) and initialised at registration:
- `level` (int ≥ 1) — current level. **Initialised to `1`** for newly registered players.
- `currentXp` (int ≥ 0) — XP accumulated **at the current level** (resets to 0 on level-up; not a lifetime total).
- `xpToNextLevel` (int > 0) — marginal cost of the next level, i.e. the value of `a × n^k + b` for `n = level + 1` from the matching `LevelBracket`. **Initialised to the marginal cost of level 2** at registration time, computed against the `LevelBracket` configuration current at that moment.

The progress bar in the header is `currentXp / xpToNextLevel`. Lifetime total XP, when needed (journal, statistics), is derived from the sum of the Player's `EarnedExperience.amount` entries — it is not stored as a separate column.

We will also introduce a new DataModel: `EarnedExperience`.
This will have:
- A label
- An amount of XP
- A date of earning
- A sourceType (quest or workout only for now)
- A sourceId
- isLocked

This data will be used to compute the total XP of a Player as well as to provide a journal of XP earned.
The current level of a player will be displayed in the header of the Player website.

##### How XP is awarded

`EarnedExperience` entries are generated by two source types:
- `quest`: when a `QuestProgression` transitions from `claimable` to `rewarded`. The amount is `quest.rewardedXP`. The `sourceId` references the `QuestProgression` (the per-period record), not the Quest, so the same Quest can yield several `EarnedExperience` entries across periods.
- `workout`: when a workout transitions to `COMPLETED`. The amount is `duration_minutes × xpPerWorkoutMinute` (rounded to the nearest integer), where `xpPerWorkoutMinute` is an admin-editable global setting (see Admin → Leveling). The `sourceId` references the workout.

### Questing

This will allow us to define Quest (in the sense of missions or tasks) that the Player can achieve to earn XP.
Quest have the following properties:
- `label`
- kind:
  - `automatic`: the quest is linked to a metric tracked elsewhere in the application; its progression updates automatically as the Player logs the underlying activity (e.g. "Drink at least 1000 mL of water" → reads hydration entries).
  - `manual`: the quest has no underlying metric; the Player ticks it as done themselves from the widget (e.g. "Meditate 10 minutes").
- metric (nullable enum, **required when kind = automatic**, **must be null when kind = manual**) — identifies which tracked value drives the progression. The exhaustive list of supported metrics is defined in a dedicated section below.
- periodicity:
  - unique: can only be completed once
  - daily: can be completed every day
  - weekly: can be completed once a week
  - monthly: can be completed once a month
- targetValue (nullable float) it will be used for quest like "Drink at least 1000mL of water". For `manual` quests, targetValue is null (the quest is binary: ticked or not).
- start date (cannot be null, by default we will set it as the day of create)
- end date (can be null, means that the quest can go on forever)
- rewardedXP (an int that cannot be null)

To be able to tell if a Player the progression of a Quest for its given period we will have a QuestProgression dataModel.
This will have the following properties :
- `quest`
- `player`
- `startDate` (nullable datetime) — start of the period covered by this progression. Set per `Quest.periodicity`:
  - `daily`: current day at `00:00:00`.
  - `weekly`: Monday of the current ISO week at `00:00:00` (the week runs Mon → Sun).
  - `monthly`: 1st day of the current calendar month at `00:00:00`.
  - `unique`: null (a unique quest has no period; only one progression exists per Player/Quest, ever).
- `endDate` (nullable datetime) — end of the period covered by this progression:
  - `daily`: current day at `23:59:59`.
  - `weekly`: Sunday of the current ISO week at `23:59:59`.
  - `monthly`: last day of the current calendar month at `23:59:59`.
  - `unique`: null.
- `completionDate` (nullable datetime) — set when the progression first transitions to `claimable` (target reached for `automatic`, or ticked by the Player for `manual`). Null while `in_progress`.
- `claimedDate` (nullable datetime) — set when the progression transitions to `rewarded` (Player claims the reward; an `EarnedExperience` entry is created). Null until then.
- `currentValue` (nullable float) — progress for quests that have a target value. For `automatic` quests it is recomputed from the underlying metric; for `manual` quests it stays null and the status alone reflects completion.
- `status` — invariant tied to the date fields:
  - `in_progress` ⇔ `completionDate` is null. Default state for `automatic` quests, while `currentValue < quest.targetValue`.
  - `claimable` ⇔ `completionDate` is set and `claimedDate` is null. Default state for `manual` quests (no progression to make — the Player only has to claim the reward); for `automatic` quests, reached when `currentValue ≥ targetValue`.
  - `rewarded` ⇔ both `completionDate` and `claimedDate` are set. The Player has claimed the reward and a corresponding `EarnedExperience` entry has been created.

**Lazy creation:** progressions for `daily` / `weekly` / `monthly` quests are auto-created on read of the widget (or any other UseCase that needs them — e.g. when an `automatic` quest's underlying metric is updated). The find-or-create logic uses (`player`, `quest`, current period boundaries) as the lookup key. `unique` progressions are auto-created the first time the unique-quests page is read.

##### Supported metrics for `automatic` quests

The exhaustive list of metrics that can drive an `automatic` Quest's progression:

| Metric | Sub-domain | Description |
|---|---|---|
| `STEPS_DAILY` | Tracking | Number of steps logged for the day. |
| `HYDRATION_ML_DAILY` | Tracking | Total hydration (mL) logged for the day. |
| `SLEEP_DURATION_MINUTES` | Tracking | Duration (minutes) of the last logged sleep entry. |
| `WORKOUT_COUNT` | Training | Number of completed workouts in the period. |
| `WORKOUT_DURATION_MINUTES` | Training | Cumulated workout duration (minutes) in the period. |

##### Example `manual` quests

`manual` quests have no underlying metric and no sub-domain. The Player ticks them from the widget to move them to `claimable`. Examples:
- "Connecte-toi aujourd'hui" (periodicity: daily) — encourages daily engagement.
- "Méditer 10 minutes" (periodicity: daily) — qualitative, not measurable in-app.

A widget will be added in the dashboard of the user to see all `daily`, `weekly`, `monthly` quests — one tab per periodicity. The widget will contain a progress bar for the daily tab (only).

`unique` quests are **not** shown in this widget. A separate page lists all `unique` quests for the Player (in progress, claimable, rewarded).

### Admin 

#### Questing

An admin can edit the following properties of a Quest: `label`, `kind`, `metric`, `targetValue`, `rewardedXP`, `dateStart`, `dateEnd`, `periodicity`. The same `kind` ⇔ `metric` consistency rules apply (`metric` non-null iff `kind = automatic`; `targetValue` null when `kind = manual`).
 
#### Leveling

An admin can manage:
- The XP curve required to level up, modelled as an ordered list of `LevelBracket` entries. Each bracket covers a contiguous range of levels and carries its own formula for the **marginal cost** (the XP required to go from level `n−1` to level `n`). Each bracket has the following properties:
  - `fromLevel` (int ≥ 1, inclusive) — first level covered by the bracket.
  - `toLevel` (nullable int, inclusive) — last level covered. `null` is allowed **only on the last (open-ended) bracket**.
  - `coefficientA` (int, non-null) — `a` in the formula.
  - `exponentK` (int ≥ 1, non-null) — `k` in the formula.
  - `offsetB` (int, non-null) — `b` in the formula.

  The marginal cost to **reach level `n`** (i.e. go from `n−1` to `n`) is `a × n^k + b`, where `a`, `k`, `b` are read from the bracket containing `n`. Example configuration (also used as the v1 seed — see Migration & seeding):
  - bracket #1: levels 1–10, `1 000 × n² + 0` → lvl 9 → 10 costs 100 000 XP.
  - bracket #2: levels 11–20, `3 000 × n² + 50 000` → lvl 10 → 11 costs `3 000 × 121 + 50 000 = 413 000` XP, lvl 19 → 20 costs `3 000 × 400 + 50 000 = 1 250 000` XP.
  - bracket #3: levels 21–∞ (open-ended), `500 × n³ + 1 000 000` → lvl 20 → 21 costs `500 × 9 261 + 1 000 000 = 5 630 500` XP.

  Validation rules:
  - The first bracket has `fromLevel = 1`.
  - Brackets are contiguous (`bracket[i+1].fromLevel = bracket[i].toLevel + 1`) and non-overlapping.
  - Exactly one bracket has `toLevel = null` (the last one).
  - The marginal cost computed for any covered level must be strictly positive.

- A global `xpPerWorkoutMinute` setting that drives how much XP a completed workout grants the Player (`duration_minutes × xpPerWorkoutMinute`, rounded). It is a **non-null integer** and **must be ≥ 50** (lower bound enforced by validation).

### Cron job

#### Leveling

A Player earns XP throughout the day, but the XP is rolled into the Player's totals only via a cron job that runs every night. The reference timezone is **Europe/Paris**: a "day" runs `00:00:00 → 23:59:59` Europe/Paris, and the cron fires once per day shortly after midnight Europe/Paris (exact schedule TBD at deployment time).

**What the cron does (sole responsibility):**
1. Select all `EarnedExperience` entries where `isLocked = false` and `earnedAt` falls strictly before the start of the current Europe/Paris day (i.e. yesterday and earlier).
2. For each Player concerned, add the selected entries' total `amount` into `Player.currentXp`, then advance levels: while `currentXp ≥ xpToNextLevel`, subtract `xpToNextLevel` from `currentXp` (overflow rolls into the new level), increment `level`, and recompute `xpToNextLevel = a × n^k + b` for `n = level + 1` from the matching `LevelBracket`.
3. Set `isLocked = true` on the consumed entries.

**Locking semantics on `EarnedExperience`:**
- A locked entry **cannot be updated or deleted** (validators reject the operation; an admin lock-lifting capability is out of v1 scope, kept open for a later iteration).
- An unlocked entry (i.e. earned today) can still be mutated by the source-side rules described below.

**Same-day vs. retroactive edits — workout case:**
- Editing a workout **on the day it was completed** propagates to its `EarnedExperience`:
  - extending / shortening the workout duration → recompute `amount = duration_minutes × xpPerWorkoutMinute` on the matching `EarnedExperience`.
  - deleting the workout (allowed on the same day, see below) → delete the matching `EarnedExperience`.
- Editing a workout **on a later day** has **no effect** on its `EarnedExperience` (which is by then locked).
- Adding a workout **retroactively** (i.e. with a completion datetime in the past) is **allowed but earns no XP** — no `EarnedExperience` entry is created for retro workouts.

**Workout deletion rule (impacts the existing Training sub-domain):**
- Deleting a workout is **only allowed on the day it was completed**. In that window, it is a hard delete (the row is removed), and the matching unlocked `EarnedExperience` is removed too.
- For any other day, the delete operation is replaced by a transition to a new `deleted` status (added to `WorkoutStatusRegistry`). The row stays in the database; the locked `EarnedExperience`, if any, is preserved untouched. UseCases listing or reading workouts must filter out `deleted` workouts by default (Player UI, planning calendar, statistics, …).

**Same-day vs. retroactive edits — quest case:**
- Claiming a `QuestProgression` reward creates the `EarnedExperience` synchronously (status moves to `rewarded`, `claimedDate` set). Until the cron locks it, the entry behaves like any other unlocked entry. After the cron, it is sealed.

#### Reporting

Eventually, a monthly report of each user's activity will be generated. The exact content is not decided yet and will be defined in a later iteration once the rest of v1 is in place. **For v1, no reporting plumbing is delivered** — neither the cron, nor the DataModel, nor the UI.

## Migration & seeding (v1 deployment)

When the v1 migrations are applied, the existing data needs to be brought to a consistent baseline:

- **Existing Players** (fixtures + already-registered users) receive the v1 defaults:
  - `level = 1`
  - `currentXp = 0`
  - `xpToNextLevel = cost(level 2)` computed against the seeded `LevelBracket` configuration (see below).
  - `dailyHydrationTargetMl = 1000`
- **Existing `COMPLETED` workouts** do **not** retroactively generate `EarnedExperience` entries (consistent with the rule "retroactive workouts earn no XP"). Players start v1 at `level = 1, currentXp = 0` regardless of their pre-v1 workout history.
- **`LevelBracket` seed** — the v1 deployment seeds the following three brackets (the configuration the user picked as v1 baseline). They can be edited later from the admin:
  - bracket #1: levels 1–10, `1 000 × n² + 0`
  - bracket #2: levels 11–20, `3 000 × n² + 50 000`
  - bracket #3: levels 21–∞ (open-ended), `500 × n³ + 1 000 000`
- **`xpPerWorkoutMinute` seed** — v1 deployment seeds the lower-bound default value `50`.

### Schema documentation cadence

Per `specifications/conventions.md`, `specifications/database-schema.html` must be regenerated whenever the database schema changes. **For v1, the regeneration is performed alongside every migration** — i.e. each Doctrine migration created during v1 development is paired with an updated `database-schema.html` in the same commit.
