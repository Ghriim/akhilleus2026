<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Sleep;

use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SleepDailyEntryDataModel>
 */
final class SleepDailyEntryRepository extends ServiceEntityRepository implements SleepDailyEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SleepDailyEntryDataModel::class);
    }

    public function findOneByPlayerAndDate(PlayerDataModel $player, \DateTimeImmutable $date): ?SleepDailyEntryDataModel
    {
        return $this->createQueryBuilder('s')
            ->where('s.player = :player')
            ->andWhere('s.date = :date')
            ->setParameter('player', $player)
            ->setParameter('date', $date->setTime(0, 0, 0))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?SleepDailyEntryDataModel
    {
        return $this->createQueryBuilder('s')
            ->where('s.id = :id')
            ->andWhere('s.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByPlayerForRange(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<SleepDailyEntryDataModel> $result */
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
