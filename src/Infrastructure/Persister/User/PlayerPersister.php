<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * @extends AbstractBaseMysqlPersister<PlayerDataModel>
 */
final readonly class PlayerPersister extends AbstractBaseMysqlPersister implements PlayerPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private UserPersisterGateway $userPersister,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(RegisterPlayerDataInput $input): PlayerDataModel
    {
        $email = $input->email;
        if ('' === $email) {
            throw new \LogicException('RegisterPlayerDataInput::$email must be non-empty before PlayerPersister::create().');
        }

        $user = new UserDataModel(
            $email,
            $input->plainPassword,
            [UserRoleRegistry::ROLE_PLAYER]
        );

        $this->userPersister->create($user);

        $player = new PlayerDataModel(
            $user,
            $input->displayName,
        );

        return $this->doCreate($player);
    }

    public function update(PlayerDataModel $model): PlayerDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(PlayerDataModel $model): void
    {
        $this->doDelete($model);
    }
}
