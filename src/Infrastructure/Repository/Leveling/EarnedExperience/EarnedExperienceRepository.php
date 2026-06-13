<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Leveling\EarnedExperience;

use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Leveling\EarnedExperience\EarnedExperienceProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EarnedExperienceDataModel>
 */
final class EarnedExperienceRepository extends ServiceEntityRepository implements EarnedExperienceProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EarnedExperienceDataModel::class);
    }

    public function findUnlockedBefore(\DateTimeImmutable $cutoff): array
    {
        /** @var list<EarnedExperienceDataModel> $result */
        $result = $this->createQueryBuilder('e')
            ->where('e.isLocked = false')
            ->andWhere('e.earnedAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->orderBy('e.player', 'ASC')
            ->addOrderBy('e.earnedAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findAllByPlayerForJournal(PlayerDataModel $player, int $page, int $perPage): array
    {
        /** @var list<EarnedExperienceDataModel> $result */
        $result = $this->createQueryBuilder('e')
            ->where('e.player = :player')
            ->setParameter('player', $player)
            ->orderBy('e.earnedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function countByPlayerForJournal(PlayerDataModel $player): int
    {
        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    public function findOneBySourceTypeAndId(string $sourceType, string $sourceId): ?EarnedExperienceDataModel
    {
        return $this->createQueryBuilder('e')
            ->where('e.sourceType = :sourceType')
            ->andWhere('e.sourceId = :sourceId')
            ->setParameter('sourceType', $sourceType)
            ->setParameter('sourceId', $sourceId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
