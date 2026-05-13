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
}
