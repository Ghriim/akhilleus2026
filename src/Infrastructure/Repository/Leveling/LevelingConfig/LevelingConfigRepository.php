<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Leveling\LevelingConfig;

use App\Domain\DTO\DataModel\Leveling\LevelingConfig\LevelingConfigDataModel;
use App\Domain\Gateway\Provider\Leveling\LevelingConfig\LevelingConfigProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LevelingConfigDataModel>
 */
final class LevelingConfigRepository extends ServiceEntityRepository implements LevelingConfigProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LevelingConfigDataModel::class);
    }

    public function getSingleton(): LevelingConfigDataModel
    {
        $config = $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->setParameter('id', LevelingConfigDataModel::LEVELING_CONFIG_ID)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $config) {
            throw new \LogicException('The leveling-config singleton is not seeded.');
        }

        return $config;
    }
}
