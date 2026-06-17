<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persister\Leveling\EarnedExperience;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use App\Infrastructure\Persister\Leveling\EarnedExperience\EarnedExperiencePersister;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EarnedExperiencePersisterTest extends KernelTestCase
{
    public function testItAllowsLockingAnUnlockedEntry(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'ee-lock-allow');
        $persister = $container->get(EarnedExperiencePersisterGateway::class);

        $entry = self::createEntry($container, $player, false);

        $entry->isLocked = true;
        $persister->update($entry);

        self::assertTrue($entry->isLocked);
    }

    public function testItRejectsUpdatingALockedEntry(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'ee-lock-update');
        $persister = $container->get(EarnedExperiencePersisterGateway::class);

        $entry = self::createEntry($container, $player, true);

        $entry->amount = 999;

        try {
            $persister->update($entry);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(EarnedExperiencePersister::EARNED_EXPERIENCE_LOCKED, $e->errorCode);
            self::assertArrayHasKey('isLocked', $e->violations);
        }
    }

    public function testItRejectsDeletingALockedEntry(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'ee-lock-delete');
        $persister = $container->get(EarnedExperiencePersisterGateway::class);

        $entry = self::createEntry($container, $player, true);

        try {
            $persister->delete($entry);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(EarnedExperiencePersister::EARNED_EXPERIENCE_LOCKED, $e->errorCode);
            self::assertArrayHasKey('isLocked', $e->violations);
        }
    }

    public function testItAllowsUpdatingAndDeletingUnlockedEntries(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'ee-unlocked-ok');
        $persister = $container->get(EarnedExperiencePersisterGateway::class);

        $entry = self::createEntry($container, $player, false);

        $entry->amount = 250;
        $persister->update($entry);
        self::assertSame(250, $entry->amount);

        $persister->delete($entry);
        $this->addToAssertionCount(1);
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Locking Hero',
        ));
    }

    private static function createEntry(
        ContainerInterface $container,
        PlayerDataModel $player,
        bool $isLocked,
    ): EarnedExperienceDataModel {
        $clock = $container->get(ClockInterface::class);

        $entry = new EarnedExperienceDataModel(
            $player,
            'Workout: Leg Day',
            100,
            $clock->now(),
            EarnedExperienceSourceTypeRegistry::WORKOUT,
            '01000000000000000000000000',
        );
        $entry->isLocked = $isLocked;

        return $container->get(EarnedExperiencePersisterGateway::class)->create($entry);
    }
}
