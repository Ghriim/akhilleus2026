<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\Gateway\Provider\Training\PersonalBest\PersonalBestProviderGateway;
use App\Domain\Registry\Training\Workout\PersonalBestTypeRegistry;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;

final readonly class PersonalBestEvaluator
{
    /**
     * The personal_best.value column has scale 4. We round every computed candidate to that
     * precision so subsequent reads + comparisons compare apples to apples.
     */
    private const int VALUE_SCALE = 4;

    public function __construct(
        private PersonalBestProviderGateway $personalBestProvider,
    ) {
    }

    /**
     * Walks every achieved set in the workout, groups them by movement, computes the seven
     * candidate metrics per group, then returns one upsert per (movement, type) tuple where
     * the candidate strictly beats the existing personal best (or none exists yet).
     *
     * @return list<PersonalBestUpsert>
     */
    public function evaluate(WorkoutDataModel $workout): array
    {
        // Defence in depth: a deleted workout must never influence personal-best recomputes.
        if (WorkoutStatusRegistry::DELETED === $workout->status) {
            return [];
        }

        if (null === $workout->dateEnd) {
            throw new \LogicException('Workout dateEnd must be set before evaluating personal bests.');
        }
        $achievedAt = $workout->dateEnd;

        /** @var array<string, array{movement: MovementDataModel, sets: list<ExerciseSetDataModel>}> $perMovement */
        $perMovement = [];
        foreach ($workout->exercises as $exercise) {
            $movementId = $exercise->movement->id;
            if (false === isset($perMovement[$movementId])) {
                $perMovement[$movementId] = ['movement' => $exercise->movement, 'sets' => []];
            }
            foreach ($exercise->exerciseSets as $set) {
                $perMovement[$movementId]['sets'][] = $set;
            }
        }

        $upserts = [];
        foreach ($perMovement as $bucket) {
            foreach (self::buildCandidates($bucket['movement'], $bucket['sets']) as $type => $candidate) {
                [$value, $sourceSet] = $candidate;
                $upsert = $this->buildUpsert($workout, $bucket['movement'], $type, $value, $sourceSet, $achievedAt);
                if (null !== $upsert) {
                    $upserts[] = $upsert;
                }
            }
        }

        return $upserts;
    }

    /**
     * @param numeric-string $candidateValue
     */
    private function buildUpsert(
        WorkoutDataModel $workout,
        MovementDataModel $movement,
        string $type,
        string $candidateValue,
        ?ExerciseSetDataModel $sourceSet,
        \DateTimeImmutable $achievedAt,
    ): ?PersonalBestUpsert {
        $existing = $this->personalBestProvider->findOneForPlayerMovementType($workout->player, $movement, $type);
        if (null !== $existing && (float) $existing->value >= (float) $candidateValue) {
            return null;
        }

        if (null === $existing) {
            $pb = new PersonalBestDataModel($workout->player, $movement, $type, $candidateValue, $achievedAt);
            $pb->workout = $workout;
            $pb->exerciseSet = $sourceSet;

            return new PersonalBestUpsert($pb, true);
        }

        $existing->value = $candidateValue;
        $existing->achievedAt = $achievedAt;
        $existing->workout = $workout;
        $existing->exerciseSet = $sourceSet;

        return new PersonalBestUpsert($existing, false);
    }

    /**
     * @param list<ExerciseSetDataModel> $sets
     *
     * @return array<string, array{0: numeric-string, 1: ?ExerciseSetDataModel}>
     */
    private static function buildCandidates(MovementDataModel $movement, array $sets): array
    {
        $candidates = [];

        if (true === $movement->tracksWeight) {
            $best = self::bestSet($sets, static fn (ExerciseSetDataModel $set) => null === $set->achievedWeight ? null : (float) $set->achievedWeight);
            if (null !== $best) {
                $candidates[PersonalBestTypeRegistry::HIGHEST_WEIGHT] = [self::format($best[0]), $best[1]];
            }
        }

        if (true === $movement->tracksRepetitions) {
            $best = self::bestSet($sets, static fn (ExerciseSetDataModel $set) => null === $set->achievedReps ? null : (float) $set->achievedReps);
            if (null !== $best) {
                $candidates[PersonalBestTypeRegistry::HIGHEST_REPS] = [self::format($best[0]), $best[1]];
            }
        }

        if (true === $movement->tracksRepetitions && true === $movement->tracksWeight) {
            $best = self::bestSet($sets, static function (ExerciseSetDataModel $set): ?float {
                if (null === $set->achievedReps || null === $set->achievedWeight) {
                    return null;
                }

                return (float) $set->achievedReps * (float) $set->achievedWeight;
            });
            if (null !== $best) {
                $candidates[PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET] = [self::format($best[0]), $best[1]];
            }

            $sum = 0.0;
            $hasAny = false;
            foreach ($sets as $set) {
                if (null === $set->achievedReps || null === $set->achievedWeight) {
                    continue;
                }
                $sum += (float) $set->achievedReps * (float) $set->achievedWeight;
                $hasAny = true;
            }
            if (true === $hasAny && 0.0 < $sum) {
                $candidates[PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT] = [self::format($sum), null];
            }
        }

        if (true === $movement->tracksDuration) {
            $best = self::bestSet($sets, static fn (ExerciseSetDataModel $set) => null === $set->achievedDurationSeconds ? null : (float) $set->achievedDurationSeconds);
            if (null !== $best) {
                $candidates[PersonalBestTypeRegistry::HIGHEST_DURATION] = [self::format($best[0]), $best[1]];
            }
        }

        if (true === $movement->tracksDistance) {
            $best = self::bestSet($sets, static fn (ExerciseSetDataModel $set) => null === $set->achievedDistanceMeters ? null : (float) $set->achievedDistanceMeters);
            if (null !== $best) {
                $candidates[PersonalBestTypeRegistry::HIGHEST_DISTANCE] = [self::format($best[0]), $best[1]];
            }
        }

        if (true === $movement->tracksDistance && true === $movement->tracksDuration) {
            $best = self::bestSet($sets, static function (ExerciseSetDataModel $set): ?float {
                if (null === $set->achievedDurationSeconds || 0 >= $set->achievedDurationSeconds || null === $set->achievedDistanceMeters) {
                    return null;
                }

                return (float) $set->achievedDistanceMeters / (float) $set->achievedDurationSeconds;
            });
            if (null !== $best) {
                $candidates[PersonalBestTypeRegistry::HIGHEST_SPEED] = [self::format($best[0]), $best[1]];
            }
        }

        return $candidates;
    }

    /**
     * @param list<ExerciseSetDataModel>             $sets
     * @param callable(ExerciseSetDataModel): ?float $extractor
     *
     * @return array{0: float, 1: ExerciseSetDataModel}|null
     */
    private static function bestSet(array $sets, callable $extractor): ?array
    {
        $bestValue = null;
        $bestSet = null;
        foreach ($sets as $set) {
            $value = $extractor($set);
            if (null === $value) {
                continue;
            }
            if (null === $bestValue || $value > $bestValue) {
                $bestValue = $value;
                $bestSet = $set;
            }
        }
        if (null === $bestValue || null === $bestSet) {
            return null;
        }

        return [$bestValue, $bestSet];
    }

    /**
     * @return numeric-string
     */
    private static function format(float $value): string
    {
        return number_format($value, self::VALUE_SCALE, '.', '');
    }
}
