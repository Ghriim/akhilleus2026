<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\Leveling\LevelingConfig;

use App\Domain\DTO\DataModel\Leveling\LevelingConfig\LevelingConfigDataModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Re-seeds the LevelingConfig singleton in dev after `doctrine:fixtures:load` purges every table
 * (the migrated baseline from Version20260613130000 would otherwise vanish locally). Unlike the
 * other fixtures, this one persists directly through the ObjectManager rather than the
 * PersisterGateway: the singleton carries a fixed well-known id, which `AbstractBaseMysqlPersister`
 * would overwrite with a fresh ULID on create — so the gateway exposes only `update` (Phase 3.4),
 * and the seed sets the id + timestamps itself.
 */
final class LevelingConfigFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $config = new LevelingConfigDataModel(50);
        $now = new \DateTimeImmutable();
        $config->createdAt = $now;
        $config->updatedAt = $now;

        $manager->persist($config);
        $manager->flush();
    }
}
