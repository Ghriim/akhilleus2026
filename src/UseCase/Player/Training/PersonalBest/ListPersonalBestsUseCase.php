<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\PersonalBest;

use App\Domain\DataTransformer\Workout\PersonalBestValueDataTransformer;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\PersonalBest\ListPersonalBestsDataInput;
use App\Domain\DTO\DataOutput\Player\Training\PersonalBest\MovementSummaryDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\PersonalBest\PersonalBestEntryDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\PersonalBest\PlayerMovementPersonalBestsDataOutput;
use App\Domain\Gateway\Provider\Training\PersonalBest\PersonalBestProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class ListPersonalBestsUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly PersonalBestProviderGateway $personalBestProvider,
    ) {
    }

    /**
     * @param ListPersonalBestsDataInput $input
     *
     * @return list<PlayerMovementPersonalBestsDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $personalBests = $this->personalBestProvider->findAllByPlayerForList($player);

        /** @var array<string, array{movement: MovementSummaryDataOutput, entries: list<PersonalBestEntryDataOutput>}> $grouped */
        $grouped = [];
        foreach ($personalBests as $personalBest) {
            $movementId = $personalBest->movement->id;
            if (false === isset($grouped[$movementId])) {
                $grouped[$movementId] = [
                    'movement' => new MovementSummaryDataOutput(
                        $personalBest->movement->id,
                        $personalBest->movement->slug,
                        $personalBest->movement->label,
                        $personalBest->movement->mainMuscle->slug,
                    ),
                    'entries' => [],
                ];
            }
            $grouped[$movementId]['entries'][] = new PersonalBestEntryDataOutput(
                $personalBest->type,
                PersonalBestValueDataTransformer::displayableValue($personalBest->type, self::formatValue($personalBest->value)),
                $personalBest->achievedAt->format(\DateTimeInterface::ATOM),
                $personalBest->workout?->id,
                $personalBest->exerciseSet?->id,
            );
        }

        return array_values(array_map(
            static fn (array $bucket) => new PlayerMovementPersonalBestsDataOutput($bucket['movement'], $bucket['entries']),
            $grouped,
        ));
    }

    /**
     * Rounds to 2 decimals, uses comma separator, drops the fractional part
     * entirely when it is zero (e.g. "100.0000" → "100", "100.5" → "100,50").
     *
     * @param numeric-string $numeric
     */
    private static function formatValue(string $numeric): string
    {
        $formatted = number_format((float) $numeric, 2, ',', '');

        return str_ends_with($formatted, ',00') ? substr($formatted, 0, -3) : $formatted;
    }
}
