<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Steps;

use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StepsDailyEntryDataModel>
 */
final class StepsDailyEntryRepository extends ServiceEntityRepository implements StepsDailyEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepsDailyEntryDataModel::class);
    }

    public function findOneByPlayerAndDate(PlayerDataModel $player, \DateTimeImmutable $date): ?StepsDailyEntryDataModel
    {
        return $this->createQueryBuilder('s')
            ->where('s.player = :player')
            ->andWhere('s.date = :date')
            ->setParameter('player', $player)
            ->setParameter('date', $date->setTime(0, 0, 0))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByPlayerForRange(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<StepsDailyEntryDataModel> $result */
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
