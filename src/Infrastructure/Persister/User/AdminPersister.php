<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User;

use App\Domain\DTO\DataInput\User\RegisterAdminDataInput;
use App\Domain\DTO\DataModel\User\AdminDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\User\AdminPersisterGateway;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * @extends AbstractBaseMysqlPersister<AdminDataModel>
 */
final readonly class AdminPersister extends AbstractBaseMysqlPersister implements AdminPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private UserPersisterGateway $userPersister,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(RegisterAdminDataInput $input): AdminDataModel
    {
        $email = $input->email;
        if ('' === $email) {
            throw new \LogicException('RegisterAdminDataInput::$email must be non-empty before AdminPersister::create().');
        }

        $user = new UserDataModel(
            $email,
            $input->plainPassword,
            [UserRoleRegistry::ROLE_ADMIN],
        );
        $this->userPersister->create($user);

        $admin = new AdminDataModel(
            $user,
            $input->firstName,
            $input->lastName,
            $input->jobTitle,
            $input->hiredAt,
        );

        return $this->doCreate($admin);
    }

    public function update(AdminDataModel $model): AdminDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(AdminDataModel $model): void
    {
        $this->doDelete($model);
    }
}
