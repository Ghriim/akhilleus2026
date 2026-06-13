<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\DeleteLevelBracketDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket\DeleteLevelBracketDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Leveling\LevelBracket\LevelBracketPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\UseCase\AbstractLoggedAdminUseCase;

final class DeleteLevelBracketUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly LevelBracketProviderGateway $levelBracketProvider,
        private readonly LevelBracketPersisterGateway $levelBracketPersister,
    ) {
    }

    /**
     * @param DeleteLevelBracketDataInput $input
     */
    public function execute(DataInputInterface $input): DeleteLevelBracketDataOutput
    {
        $bracket = $this->levelBracketProvider->findOneByIdForAdminAction($input->id);
        if (null === $bracket) {
            throw new EntityNotFoundException(sprintf('Level bracket "%s" not found.', $input->id));
        }

        $this->levelBracketPersister->delete($bracket);

        return new DeleteLevelBracketDataOutput($input->id);
    }
}
