<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\GetLevelBracketDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket\LevelBracketDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class GetLevelBracketDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private LevelBracketProviderGateway $levelBracketProvider,
        private ObjectMapperInterface $mapper,
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

        return $this->mapper->map($bracket, LevelBracketDataOutput::class);
    }
}
