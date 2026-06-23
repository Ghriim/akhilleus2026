<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\ListLevelBracketsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket\LevelBracketListItemDataOutput;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ListLevelBracketsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private LevelBracketProviderGateway $levelBracketProvider,
        private ObjectMapperInterface $mapper,
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
            fn (LevelBracketDataModel $bracket): LevelBracketListItemDataOutput => $this->mapper->map($bracket, LevelBracketListItemDataOutput::class),
            $this->levelBracketProvider->findAllOrderedAsc(),
        );
    }
}
