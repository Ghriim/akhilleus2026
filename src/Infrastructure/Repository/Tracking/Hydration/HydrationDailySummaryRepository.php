<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDailySummaryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HydrationDailySummaryDataModel>
 */
final class HydrationDailySummaryRepository extends ServiceEntityRepository implements HydrationDailySummaryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HydrationDailySummaryDataModel::class);
    }

    public function findOneByPlayerAndDateWithEntries(PlayerDataModel $player, \DateTimeImmutable $date): ?HydrationDailySummaryDataModel
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.entries', 'e')->addSelect('e')
            ->where('s.player = :player')
            ->andWhere('s.date = :date')
            ->setParameter('player', $player)
            ->setParameter('date', $date->setTime(0, 0, 0))
            ->orderBy('e.loggedAt', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByPlayerForRange(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<HydrationDailySummaryDataModel> $result */
        $result = $this->createQueryBuilder('s')
            ->where('s.player = :player')
            ->andWhere('s.date BETWEEN :from AND :to')
            ->setParameter('player', $player)
            ->setParameter('from', $from->setTime(0, 0, 0))
            ->setParameter('to', $to->setTime(0, 0, 0))
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
