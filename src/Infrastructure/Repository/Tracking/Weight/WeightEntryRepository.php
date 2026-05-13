<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Weight;

use App\Domain\DTO\DataModel\Tracking\Weight\WeightEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeightEntryDataModel>
 */
final class WeightEntryRepository extends ServiceEntityRepository implements WeightEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeightEntryDataModel::class);
    }

    public function findOneByPlayerAndDate(PlayerDataModel $player, \DateTimeImmutable $date): ?WeightEntryDataModel
    {
        return $this->createQueryBuilder('w')
            ->where('w.player = :player')
            ->andWhere('w.date = :date')
            ->setParameter('player', $player)
            ->setParameter('date', $date->setTime(0, 0, 0))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?WeightEntryDataModel
    {
        return $this->createQueryBuilder('w')
            ->where('w.id = :id')
            ->andWhere('w.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByPlayerForRange(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<WeightEntryDataModel> $result */
        $result = $this->createQueryBuilder('w')
            ->where('w.player = :player')
            ->andWhere('w.loggedAt BETWEEN :from AND :to')
            ->setParameter('player', $player)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('w.loggedAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
