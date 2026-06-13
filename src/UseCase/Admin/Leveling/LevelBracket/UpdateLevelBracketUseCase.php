<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\UpdateLevelBracketDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket\LevelBracketDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Leveling\LevelBracket\LevelBracketPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\Domain\Validator\Admin\Leveling\LevelBracket\UpdateLevelBracketValidator;
use App\UseCase\AbstractLoggedAdminUseCase;

final class UpdateLevelBracketUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly UpdateLevelBracketValidator $updateLevelBracketValidator,
        private readonly LevelBracketProviderGateway $levelBracketProvider,
        private readonly LevelBracketPersisterGateway $levelBracketPersister,
    ) {
    }

    /**
     * @param UpdateLevelBracketDataInput $input
     */
    public function execute(DataInputInterface $input): LevelBracketDataOutput
    {
        $bracket = $this->levelBracketProvider->findOneByIdForAdminAction($input->id);
        if (null === $bracket) {
            throw new EntityNotFoundException(sprintf('Level bracket "%s" not found.', $input->id));
        }

        $this->updateLevelBracketValidator->validate($input);

        $bracket->fromLevel = $input->fromLevel;
        $bracket->toLevel = $input->toLevel;
        $bracket->coefficientA = $input->coefficientA;
        $bracket->exponentK = $input->exponentK;
        $bracket->offsetB = $input->offsetB;
        $this->levelBracketPersister->update($bracket);

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
