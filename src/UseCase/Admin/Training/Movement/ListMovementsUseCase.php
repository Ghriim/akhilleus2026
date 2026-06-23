<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\ListMovementsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Movement\MovementListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Validator\Admin\Training\Movement\ListMovementsValidator;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ListMovementsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private ListMovementsValidator $listMovementsValidator,
        private MovementProviderGateway $movementProvider,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param ListMovementsDataInput $input
     *
     * @return list<MovementListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $this->listMovementsValidator->validate($input);

        $movements = $this->movementProvider->findAllForAdminList($input->sort, $input->direction);

        $outputs = [];
        foreach ($movements as $movement) {
            $outputs[] = $this->mapper->map($movement, MovementListItemDataOutput::class);
        }

        return $outputs;
    }
}
