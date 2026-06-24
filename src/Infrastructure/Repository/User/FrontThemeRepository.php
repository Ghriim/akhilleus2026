<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\User;

use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FrontThemeDataModel>
 */
final class FrontThemeRepository extends ServiceEntityRepository implements FrontThemeProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FrontThemeDataModel::class);
    }

    public function findOneByIdForAdminAction(string $id): ?FrontThemeDataModel
    {
        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByNameForUniqueness(string $name): ?FrontThemeDataModel
    {
        return $this->createQueryBuilder('t')
            ->where('t.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllForAdminList(): array
    {
        /** @var list<FrontThemeDataModel> $result */
        $result = $this->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
