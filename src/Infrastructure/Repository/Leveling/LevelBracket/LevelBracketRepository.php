<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Leveling\LevelBracket;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LevelBracketDataModel>
 */
final class LevelBracketRepository extends ServiceEntityRepository implements LevelBracketProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LevelBracketDataModel::class);
    }

    public function findAllOrderedAsc(): array
    {
        /** @var list<LevelBracketDataModel> $result */
        $result = $this->createQueryBuilder('b')
            ->orderBy('b.fromLevel', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findContainingLevel(int $level): ?LevelBracketDataModel
    {
        return $this->createQueryBuilder('b')
            ->where('b.fromLevel <= :level')
            ->andWhere('b.toLevel IS NULL OR b.toLevel >= :level')
            ->setParameter('level', $level)
            ->orderBy('b.fromLevel', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByIdForAdminAction(string $id): ?LevelBracketDataModel
    {
        return $this->createQueryBuilder('b')
            ->where('b.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
