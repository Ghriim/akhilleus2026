<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Questing\QuestProgression;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Questing\QuestProgression\QuestProgressionProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestProgressionDataModel>
 */
final class QuestProgressionRepository extends ServiceEntityRepository implements QuestProgressionProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestProgressionDataModel::class);
    }

    public function findOneByPlayerQuestPeriod(
        PlayerDataModel $player,
        QuestDataModel $quest,
        ?\DateTimeImmutable $startDate,
    ): ?QuestProgressionDataModel {
        $qb = $this->createQueryBuilder('qp')
            ->where('qp.player = :player')
            ->andWhere('qp.quest = :quest')
            ->setParameter('player', $player)
            ->setParameter('quest', $quest)
            ->setMaxResults(1);

        if (null === $startDate) {
            $qb->andWhere('qp.startDate IS NULL');
        } else {
            $qb->andWhere('qp.startDate = :startDate')->setParameter('startDate', $startDate);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllByPlayerActiveDaily(PlayerDataModel $player, \DateTimeImmutable $now): array
    {
        return $this->findAllByPlayerActivePeriodicity($player, QuestPeriodicityRegistry::DAILY, $now);
    }

    public function findAllByPlayerActiveWeekly(PlayerDataModel $player, \DateTimeImmutable $now): array
    {
        return $this->findAllByPlayerActivePeriodicity($player, QuestPeriodicityRegistry::WEEKLY, $now);
    }

    public function findAllByPlayerActiveMonthly(PlayerDataModel $player, \DateTimeImmutable $now): array
    {
        return $this->findAllByPlayerActivePeriodicity($player, QuestPeriodicityRegistry::MONTHLY, $now);
    }

    public function findAllUniqueByPlayer(PlayerDataModel $player): array
    {
        /** @var list<QuestProgressionDataModel> $result */
        $result = $this->createQueryBuilder('qp')
            ->innerJoin('qp.quest', 'q')
            ->addSelect('q')
            ->where('qp.player = :player')
            ->andWhere('q.periodicity = :periodicity')
            ->setParameter('player', $player)
            ->setParameter('periodicity', QuestPeriodicityRegistry::UNIQUE)
            ->orderBy('qp.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?QuestProgressionDataModel
    {
        return $this->createQueryBuilder('qp')
            ->innerJoin('qp.quest', 'q')
            ->addSelect('q')
            ->where('qp.id = :id')
            ->andWhere('qp.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * The player's progressions whose period contains `$now`, joined to active quests of the given
     * periodicity (`dateStart ≤ now` and `dateEnd` null-or-future).
     *
     * @return list<QuestProgressionDataModel>
     */
    private function findAllByPlayerActivePeriodicity(
        PlayerDataModel $player,
        string $periodicity,
        \DateTimeImmutable $now,
    ): array {
        /** @var list<QuestProgressionDataModel> $result */
        $result = $this->createQueryBuilder('qp')
            ->innerJoin('qp.quest', 'q')
            ->addSelect('q')
            ->where('qp.player = :player')
            ->andWhere('q.periodicity = :periodicity')
            ->andWhere('qp.startDate <= :now')
            ->andWhere('qp.endDate >= :now')
            ->andWhere('q.dateStart <= :now')
            ->andWhere('(q.dateEnd IS NULL OR q.dateEnd >= :now)')
            ->setParameter('player', $player)
            ->setParameter('periodicity', $periodicity)
            ->setParameter('now', $now)
            ->orderBy('q.dateStart', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
