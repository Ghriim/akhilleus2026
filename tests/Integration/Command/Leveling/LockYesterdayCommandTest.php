<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Leveling;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\EarnedExperience\EarnedExperienceProviderGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Ulid;

/**
 * End-to-end coverage of `app:leveling:lock-yesterday` against the real DB. The brackets seeded by
 * Version20260613120000 give marginalCostFor(2) = 1000·2² = 4000 and marginalCostFor(3) = 9000, so
 * a fresh player starts at level=1, currentXp=0, xpToNextLevel=4000. The `--cutoff` override pins
 * the day boundary deterministically (today 00:00 Europe/Paris in these tests).
 */
final class LockYesterdayCommandTest extends KernelTestCase
{
    private const string CUTOFF = '2026-06-17T00:00:00+02:00';
    private const string YESTERDAY = '2026-06-16T20:00:00+02:00';
    private const string TODAY = '2026-06-17T08:00:00+02:00';

    public function testItLocksEntriesBeforeCutoffAndAdvancesThePlayerLevel(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();
        $player = self::createPlayer($container, 'lock-single');
        $sourceA = self::seedEntry($container, $player, 3000, self::YESTERDAY);
        $sourceB = self::seedEntry($container, $player, 2000, self::YESTERDAY);
        $sourceToday = self::seedEntry($container, $player, 9000, self::TODAY);

        $tester = self::runCommand($kernel, self::CUTOFF);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString(
            'Locked 2 entries across 1 players, awarding 5000 XP',
            self::normalize($tester->getDisplay()),
        );

        $container->get(EntityManagerInterface::class)->clear();
        $provider = $container->get(EarnedExperienceProviderGateway::class);

        // 3000 + 2000 = 5000 ≥ 4000 → one level-up: currentXp 5000-4000 = 1000, level 2, next = cost(3) = 9000.
        $reloaded = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceA);
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isLocked);
        self::assertSame(2, $reloaded->player->level);
        self::assertSame(1000, $reloaded->player->currentXp);
        self::assertSame(9000, $reloaded->player->xpToNextLevel);

        $other = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceB);
        self::assertNotNull($other);
        self::assertTrue($other->isLocked);

        // The entry earned today (≥ cutoff) is left untouched for the next night.
        $today = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceToday);
        self::assertNotNull($today);
        self::assertFalse($today->isLocked);
        self::assertSame(9000, $today->amount);
    }

    public function testItGroupsByPlayerAndLevelsEachIndependently(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();
        $playerA = self::createPlayer($container, 'lock-multi-a');
        $playerB = self::createPlayer($container, 'lock-multi-b');
        $sourceA = self::seedEntry($container, $playerA, 1000, self::YESTERDAY);
        $sourceB = self::seedEntry($container, $playerB, 4500, self::YESTERDAY);

        $tester = self::runCommand($kernel, self::CUTOFF);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString(
            'Locked 2 entries across 2 players, awarding 5500 XP',
            self::normalize($tester->getDisplay()),
        );

        $container->get(EntityManagerInterface::class)->clear();
        $provider = $container->get(EarnedExperienceProviderGateway::class);

        // Player A: 1000 < 4000 → stays level 1, currentXp 1000.
        $a = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceA);
        self::assertNotNull($a);
        self::assertTrue($a->isLocked);
        self::assertSame(1, $a->player->level);
        self::assertSame(1000, $a->player->currentXp);

        // Player B: 4500 ≥ 4000 → level 2, currentXp 500, next = cost(3) = 9000.
        $b = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceB);
        self::assertNotNull($b);
        self::assertTrue($b->isLocked);
        self::assertSame(2, $b->player->level);
        self::assertSame(500, $b->player->currentXp);
        self::assertSame(9000, $b->player->xpToNextLevel);
    }

    public function testItIsANoOpWhenNothingPredatesTheCutoff(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();
        $player = self::createPlayer($container, 'lock-noop');
        $source = self::seedEntry($container, $player, 1234, self::TODAY);

        $tester = self::runCommand($kernel, self::CUTOFF);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString(
            'Locked 0 entries across 0 players, awarding 0 XP',
            self::normalize($tester->getDisplay()),
        );

        $container->get(EntityManagerInterface::class)->clear();
        $reloaded = $container->get(EarnedExperienceProviderGateway::class)
            ->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $source);
        self::assertNotNull($reloaded);
        self::assertFalse($reloaded->isLocked, 'A same-day entry must not be locked.');
        self::assertSame(1, $reloaded->player->level);
        self::assertSame(0, $reloaded->player->currentXp);
    }

    public function testItIsIdempotentAndSkipsAlreadyLockedEntries(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();
        $player = self::createPlayer($container, 'lock-idempotent');
        $source = self::seedEntry($container, $player, 5000, self::YESTERDAY);

        self::runCommand($kernel, self::CUTOFF);

        // Second run: the entry is already locked → findUnlockedBefore returns nothing → no re-credit.
        $second = self::runCommand($kernel, self::CUTOFF);
        self::assertSame(Command::SUCCESS, $second->getStatusCode());
        self::assertStringContainsString(
            'Locked 0 entries across 0 players, awarding 0 XP',
            self::normalize($second->getDisplay()),
        );

        $container->get(EntityManagerInterface::class)->clear();
        $reloaded = $container->get(EarnedExperienceProviderGateway::class)
            ->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $source);
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isLocked);
        // The 5000 XP was credited exactly once: level 2, currentXp 1000 (not 2 level-ups).
        self::assertSame(2, $reloaded->player->level);
        self::assertSame(1000, $reloaded->player->currentXp);
    }

    public function testItRejectsAnInvalidCutoff(): void
    {
        $kernel = self::bootKernel();

        $tester = self::runCommand($kernel, 'not-a-date');

        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertStringContainsString('Invalid --cutoff value', $tester->getDisplay());
    }

    private static function runCommand(object $kernel, string $cutoff): CommandTester
    {
        /** @var \Symfony\Component\HttpKernel\KernelInterface $kernel */
        $application = new Application($kernel);
        $tester = new CommandTester($application->find('app:leveling:lock-yesterday'));
        $tester->execute(['--cutoff' => $cutoff]);

        return $tester;
    }

    private static function createPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Lock Hero',
        ));
    }

    private static function seedEntry(ContainerInterface $container, PlayerDataModel $player, int $amount, string $earnedAt): string
    {
        $sourceId = (string) new Ulid();
        $container->get(EarnedExperiencePersisterGateway::class)->create(new EarnedExperienceDataModel(
            $player,
            'Test grant',
            $amount,
            new \DateTimeImmutable($earnedAt),
            EarnedExperienceSourceTypeRegistry::WORKOUT,
            $sourceId,
        ));

        return $sourceId;
    }

    private static function normalize(string $display): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $display));
    }
}
