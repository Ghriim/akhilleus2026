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
     * The `$sort` and `$direction` arguments are interpolated into the ORDER BY clause —
     * the use case (List*Validator) is responsible for whitelisting them against
     * `ListEquipmentsDataInput::ALLOWED_SORTS` + `ASC|DESC` before reaching this method.
     *
     * @return list<EquipmentDataModel>
     */
    public function findAllForAdminList(string $sort = 'label', string $direction = 'ASC'): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy("e.{$sort}", $direction)
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
