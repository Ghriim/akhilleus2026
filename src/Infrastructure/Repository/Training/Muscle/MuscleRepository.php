<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training\Muscle;

use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MuscleDataModel>
 */
final class MuscleRepository extends ServiceEntityRepository implements MuscleProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MuscleDataModel::class);
    }

    public function findOneForAdminDetails(string $id): ?MuscleDataModel
    {
        return $this->createQueryBuilder('m')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * The `$sort` and `$direction` arguments are interpolated into the ORDER BY clause —
     * the use case (List*Validator) is responsible for whitelisting them against
     * `ListMusclesDataInput::ALLOWED_SORTS` + `ASC|DESC` before reaching this method.
     *
     * @return list<MuscleDataModel>
     */
    public function findAllForAdminList(string $sort = 'label', string $direction = 'ASC'): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy("m.{$sort}", $direction)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlugForUniqueness(string $slug): ?MuscleDataModel
    {
        return $this->createQueryBuilder('m')
            ->where('m.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
