<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\User;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDataModel>
 */
final class UserRepository extends ServiceEntityRepository implements UserProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDataModel::class);
    }

    public function findOneByEmailForAuthentication(string $email): ?UserDataModel
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmailForUniquenessCheck(string $email): ?UserDataModel
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
