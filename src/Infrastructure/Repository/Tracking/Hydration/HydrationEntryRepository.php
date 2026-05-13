<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationEntryProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HydrationEntryDataModel>
 */
final class HydrationEntryRepository extends ServiceEntityRepository implements HydrationEntryProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HydrationEntryDataModel::class);
    }

    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?HydrationEntryDataModel
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.summary', 's')->addSelect('s')
            ->where('e.id = :id')
            ->andWhere('s.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
