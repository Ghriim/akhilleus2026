<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training\Equipment;

use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EquipmentDataModel>
 */
final class EquipmentRepository extends ServiceEntityRepository implements EquipmentProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipmentDataModel::class);
    }

    public function findOneForAdminDetails(string $id): ?EquipmentDataModel
    {
        return $this->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<EquipmentDataModel>
     */
    public function findAllForAdminList(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlugForUniqueness(string $slug): ?EquipmentDataModel
    {
        return $this->createQueryBuilder('e')
            ->where('e.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
