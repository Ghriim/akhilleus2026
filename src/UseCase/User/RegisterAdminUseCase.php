<?php

declare(strict_types=1);

namespace App\UseCase\User;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\User\RegisterAdminDataInput;
use App\Domain\DTO\DataOutput\User\RegisterAdminDataOutput;
use App\Domain\Gateway\Persister\User\AdminPersisterGateway;
use App\Domain\Validator\User\RegisterAdminValidator;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class RegisterAdminUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private RegisterAdminValidator $registerAdminValidator,
        private AdminPersisterGateway $adminPersister,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param RegisterAdminDataInput $input
     */
    public function execute(DataInputInterface $input): RegisterAdminDataOutput
    {
        $this->registerAdminValidator->validate($input);

        $admin = $this->adminPersister->create($input);

        return $this->mapper->map($admin, RegisterAdminDataOutput::class);
    }
}
