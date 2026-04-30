<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training\Movement;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementDataModel>
 */
final class MovementRepository extends ServiceEntityRepository implements MovementProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementDataModel::class);
    }

    public function findOneForAdminDetails(string $id): ?MovementDataModel
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.mainMuscle', 'mm')->addSelect('mm')
            ->leftJoin('m.secondaryMuscles', 'sm')->addSelect('sm')
            ->leftJoin('m.equipments', 'eq')->addSelect('eq')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * The `$sort` and `$direction` arguments are interpolated into the ORDER BY clause —
     * the use case (List*Validator) is responsible for whitelisting them against
     * `ListMovementsDataInput::ALLOWED_SORTS` + `ASC|DESC` before reaching this method.
     *
     * @return list<MovementDataModel>
     */
    public function findAllForAdminList(string $sort = 'label', string $direction = 'ASC'): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.mainMuscle', 'mm')->addSelect('mm')
            ->leftJoin('m.secondaryMuscles', 'sm')->addSelect('sm')
            ->leftJoin('m.equipments', 'eq')->addSelect('eq')
            ->orderBy("m.{$sort}", $direction)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlugForUniqueness(string $slug): ?MovementDataModel
    {
        return $this->createQueryBuilder('m')
            ->where('m.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
