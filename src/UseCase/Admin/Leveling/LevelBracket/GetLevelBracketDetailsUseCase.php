<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\GetLevelBracketDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket\LevelBracketDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\UseCase\AbstractPublicUseCase;

final class GetLevelBracketDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly LevelBracketProviderGateway $levelBracketProvider,
    ) {
    }

    /**
     * @param GetLevelBracketDetailsDataInput $input
     */
    public function execute(DataInputInterface $input): LevelBracketDataOutput
    {
        $bracket = $this->levelBracketProvider->findOneByIdForAdminAction($input->id);
        if (null === $bracket) {
            throw new EntityNotFoundException(sprintf('Level bracket "%s" not found.', $input->id));
        }

        return new LevelBracketDataOutput(
            $bracket->id,
            $bracket->fromLevel,
            $bracket->toLevel,
            $bracket->coefficientA,
            $bracket->exponentK,
            $bracket->offsetB,
        );
    }
}
