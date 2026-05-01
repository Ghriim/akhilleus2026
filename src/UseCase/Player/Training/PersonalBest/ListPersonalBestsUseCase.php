<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\PersonalBest;

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
        $rows = $this->personalBestProvider->findAllByPlayerForList($player);

        /** @var array<string, array{movement: MovementSummaryDataOutput, entries: list<PersonalBestEntryDataOutput>}> $grouped */
        $grouped = [];
        foreach ($rows as $row) {
            $movementId = $row->movement->id;
            if (false === isset($grouped[$movementId])) {
                $grouped[$movementId] = [
                    'movement' => new MovementSummaryDataOutput(
                        $row->movement->id,
                        $row->movement->slug,
                        $row->movement->label,
                        $row->movement->mainMuscle->slug,
                    ),
                    'entries' => [],
                ];
            }
            $grouped[$movementId]['entries'][] = new PersonalBestEntryDataOutput(
                $row->type,
                $row->value,
                $row->achievedAt->format(\DateTimeInterface::ATOM),
                $row->workout?->id,
                $row->exerciseSet?->id,
            );
        }

        return array_values(array_map(
            static fn (array $bucket) => new PlayerMovementPersonalBestsDataOutput($bucket['movement'], $bucket['entries']),
            $grouped,
        ));
    }
}
