<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Questing\Quest;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestDataModel>
 */
final class QuestRepository extends ServiceEntityRepository implements QuestProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestDataModel::class);
    }

    public function findActiveAtForList(\DateTimeImmutable $now): array
    {
        /** @var list<QuestDataModel> $result */
        $result = $this->activeAtQueryBuilder($now)
            ->orderBy('q.dateStart', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findActiveByPeriodicityForPlayer(string $periodicity, \DateTimeImmutable $now): array
    {
        /** @var list<QuestDataModel> $result */
        $result = $this->activeAtQueryBuilder($now)
            ->andWhere('q.periodicity = :periodicity')
            ->setParameter('periodicity', $periodicity)
            ->orderBy('q.dateStart', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findAllForAdminList(): array
    {
        /** @var list<QuestDataModel> $result */
        $result = $this->createQueryBuilder('q')
            ->orderBy('q.dateStart', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findOneByIdForAdminAction(string $id): ?QuestDataModel
    {
        return $this->createQueryBuilder('q')
            ->where('q.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveAutomaticByMetric(string $metric, \DateTimeImmutable $now): array
    {
        /** @var list<QuestDataModel> $result */
        $result = $this->activeAtQueryBuilder($now)
            ->andWhere('q.kind = :kind')
            ->andWhere('q.metric = :metric')
            ->setParameter('kind', QuestKindRegistry::AUTOMATIC)
            ->setParameter('metric', $metric)
            ->orderBy('q.dateStart', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    private function activeAtQueryBuilder(\DateTimeImmutable $now): QueryBuilder
    {
        // The OR must be parenthesised: Doctrine appends andWhere() strings verbatim, and SQL's
        // AND-binds-tighter-than-OR precedence would otherwise match not-yet-started quests.
        return $this->createQueryBuilder('q')
            ->where('q.dateStart <= :now')
            ->andWhere('(q.dateEnd IS NULL OR q.dateEnd >= :now)')
            ->setParameter('now', $now);
    }
}
