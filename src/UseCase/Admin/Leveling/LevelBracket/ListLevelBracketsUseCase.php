<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\ListLevelBracketsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket\LevelBracketListItemDataOutput;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\UseCase\AbstractPublicUseCase;

final class ListLevelBracketsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly LevelBracketProviderGateway $levelBracketProvider,
    ) {
    }

    /**
     * @param ListLevelBracketsDataInput $input
     *
     * @return list<LevelBracketListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        return array_map(
            static fn ($bracket) => new LevelBracketListItemDataOutput(
                $bracket->id,
                $bracket->fromLevel,
                $bracket->toLevel,
                $bracket->coefficientA,
                $bracket->exponentK,
                $bracket->offsetB,
            ),
            $this->levelBracketProvider->findAllOrderedAsc(),
        );
    }
}
