<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\User;

use App\Domain\DTO\DataInput\User\RegisterAdminDataInput;
use App\Domain\Gateway\Persister\User\AdminPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AdminFixtures extends Fixture
{
    public const string REFERENCE_ADMIN = 'admin-profile';

    public function __construct(
        private readonly AdminPersisterGateway $adminPersister,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->adminPersister->create(new RegisterAdminDataInput(
            'admin@akhilleus.test',
            'AdminAdmin1!',
            'Admin',
            'Hero',
            'Platform Administrator',
            new \DateTimeImmutable('2026-01-01'),
        ));
        $this->addReference(self::REFERENCE_ADMIN, $admin);
    }
}
