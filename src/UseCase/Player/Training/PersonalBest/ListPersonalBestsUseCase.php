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
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListPersonalBestsUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly PersonalBestProviderGateway $personalBestProvider,
        private readonly ObjectMapperInterface $mapper,
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
                    'movement' => $this->mapper->map($personalBest->movement, MovementSummaryDataOutput::class),
                    'entries' => [],
                ];
            }
            $grouped[$movementId]['entries'][] = $this->mapper->map($personalBest, PersonalBestEntryDataOutput::class);
        }

        return array_values(array_map(
            static fn (array $bucket) => new PlayerMovementPersonalBestsDataOutput($bucket['movement'], $bucket['entries']),
            $grouped,
        ));
    }
}
