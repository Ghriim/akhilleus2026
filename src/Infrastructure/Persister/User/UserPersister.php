<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserPersister extends AbstractBaseMysqlPersister implements UserPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(UserDataModel $model): UserDataModel
    {
        if (null === $model->plainPassword) {
            throw new \LogicException('UserDataModel::$plainPassword must be set before UserPersister::create().');
        }

        $model->password = $this->passwordHasher->hashPassword($model, $model->plainPassword);
        $this->doCreate($model);

        return $model;
    }

    public function update(UserDataModel $model): UserDataModel
    {
        if (null !== $model->plainPassword) {
            $model->password = $this->passwordHasher->hashPassword($model, $model->plainPassword);
        }

        $this->doUpdate($model);

        return $model;
    }

    public function delete(UserDataModel $model): void
    {
        $this->doDelete($model);
    }
}
