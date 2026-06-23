<?php

declare(strict_types=1);

namespace App\UseCase\Player\Profile;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Profile\GetPlayerProfileDataInput;
use App\Domain\DTO\DataOutput\Player\Profile\PlayerProfileDataOutput;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class GetPlayerProfileUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param GetPlayerProfileDataInput $input
     */
    public function execute(DataInputInterface $input): PlayerProfileDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        return $this->mapper->map($player, PlayerProfileDataOutput::class);
    }
}
