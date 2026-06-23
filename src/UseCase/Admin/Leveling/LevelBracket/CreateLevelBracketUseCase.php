<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\CreateLevelBracketDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket\LevelBracketDataOutput;
use App\Domain\Gateway\Persister\Leveling\LevelBracket\LevelBracketPersisterGateway;
use App\Domain\Validator\Admin\Leveling\LevelBracket\CreateLevelBracketValidator;
use App\UseCase\AbstractLoggedAdminUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class CreateLevelBracketUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly CreateLevelBracketValidator $createLevelBracketValidator,
        private readonly LevelBracketPersisterGateway $levelBracketPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param CreateLevelBracketDataInput $input
     */
    public function execute(DataInputInterface $input): LevelBracketDataOutput
    {
        $this->createLevelBracketValidator->validate($input);

        $bracket = $this->levelBracketPersister->create(new LevelBracketDataModel(
            $input->fromLevel,
            $input->toLevel,
            $input->coefficientA,
            $input->exponentK,
            $input->offsetB,
        ));

        return $this->mapper->map($bracket, LevelBracketDataOutput::class);
    }
}
