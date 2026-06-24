<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Leveling;

use App\Domain\DTO\DataInput\Leveling\LockEarnedExperienceDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\EarnedExperience\EarnedExperienceProviderGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use App\UseCase\Leveling\LockEarnedExperienceUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Direct coverage of the use case the nightly cron delegates to. The brackets seeded by
 * Version20260613120000 give marginalCostFor(2) = 1000·2² = 4000, so a fresh player starts at
 * level=1, currentXp=0, xpToNextLevel=4000. The cutoff is passed explicitly to pin the day
 * boundary deterministically (today 00:00 Europe/Paris in these tests).
 */
final class LockEarnedExperienceUseCaseTest extends KernelTestCase
{
    private const string CUTOFF = '2026-06-17T00:00:00+02:00';
    private const string YESTERDAY = '2026-06-16T20:00:00+02:00';
    private const string TODAY = '2026-06-17T08:00:00+02:00';

    public function testItLocksEntriesBeforeCutoffAndAdvancesThePlayerLevel(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createPlayer($container, 'uc-lock-single');
        $sourceA = self::seedEntry($container, $player, 3000, self::YESTERDAY);
        $sourceB = self::seedEntry($container, $player, 2000, self::YESTERDAY);
        $sourceToday = self::seedEntry($container, $player, 9000, self::TODAY);

        $output = $container->get(LockEarnedExperienceUseCase::class)->execute(
            new LockEarnedExperienceDataInput(new \DateTimeImmutable(self::CUTOFF)),
        );

        self::assertSame(2, $output->entriesLocked);
        self::assertSame(1, $output->playersTouched);
        self::assertSame(5000, $output->totalXpAwarded);
        self::assertSame(self::CUTOFF, $output->cutoff);

        $container->get(EntityManagerInterface::class)->clear();
        $provider = $container->get(EarnedExperienceProviderGateway::class);

        // 3000 + 2000 = 5000 ≥ 4000 → one level-up: currentXp 5000-4000 = 1000, level 2, next = cost(3) = 9000.
        $a = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceA);
        self::assertNotNull($a);
        self::assertTrue($a->isLocked);
        self::assertSame(2, $a->player->level);
        self::assertSame(1000, $a->player->currentXp);
        self::assertSame(9000, $a->player->xpToNextLevel);

        $b = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceB);
        self::assertNotNull($b);
        self::assertTrue($b->isLocked);

        // The entry earned today (≥ cutoff) is left untouched for the next night.
        $today = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceToday);
        self::assertNotNull($today);
        self::assertFalse($today->isLocked);
    }

    public function testItGroupsByPlayerAndLevelsEachIndependently(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $playerA = self::createPlayer($container, 'uc-lock-multi-a');
        $playerB = self::createPlayer($container, 'uc-lock-multi-b');
        $sourceA = self::seedEntry($container, $playerA, 1000, self::YESTERDAY);
        $sourceB = self::seedEntry($container, $playerB, 4500, self::YESTERDAY);

        $output = $container->get(LockEarnedExperienceUseCase::class)->execute(
            new LockEarnedExperienceDataInput(new \DateTimeImmutable(self::CUTOFF)),
        );

        self::assertSame(2, $output->entriesLocked);
        self::assertSame(2, $output->playersTouched);
        self::assertSame(5500, $output->totalXpAwarded);

        $container->get(EntityManagerInterface::class)->clear();
        $provider = $container->get(EarnedExperienceProviderGateway::class);

        // Player A: 1000 < 4000 → stays level 1, currentXp 1000.
        $a = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceA);
        self::assertNotNull($a);
        self::assertSame(1, $a->player->level);
        self::assertSame(1000, $a->player->currentXp);

        // Player B: 4500 ≥ 4000 → level 2, currentXp 500, next = cost(3) = 9000.
        $b = $provider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $sourceB);
        self::assertNotNull($b);
        self::assertSame(2, $b->player->level);
        self::assertSame(500, $b->player->currentXp);
        self::assertSame(9000, $b->player->xpToNextLevel);
    }

    public function testItDefaultsTheCutoffWhenNoneIsProvided(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createPlayer($container, 'uc-lock-default');
        // Earned "now" (≥ today 00:00 Europe/Paris) → above the default cutoff → left unlocked.
        $source = self::seedEntry($container, $player, 1234, (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM));

        $output = $container->get(LockEarnedExperienceUseCase::class)->execute(
            new LockEarnedExperienceDataInput(),
        );

        self::assertSame(0, $output->entriesLocked);
        self::assertNotSame('', $output->cutoff, 'cutoff is defaulted and echoed back');

        $container->get(EntityManagerInterface::class)->clear();
        $reloaded = $container->get(EarnedExperienceProviderGateway::class)
            ->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $source);
        self::assertNotNull($reloaded);
        self::assertFalse($reloaded->isLocked);
    }

    public function testItIsIdempotentAndSkipsAlreadyLockedEntries(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createPlayer($container, 'uc-lock-idempotent');
        $source = self::seedEntry($container, $player, 5000, self::YESTERDAY);
        $useCase = $container->get(LockEarnedExperienceUseCase::class);

        $useCase->execute(new LockEarnedExperienceDataInput(new \DateTimeImmutable(self::CUTOFF)));
        $second = $useCase->execute(new LockEarnedExperienceDataInput(new \DateTimeImmutable(self::CUTOFF)));

        self::assertSame(0, $second->entriesLocked);
        self::assertSame(0, $second->totalXpAwarded);

        $container->get(EntityManagerInterface::class)->clear();
        $reloaded = $container->get(EarnedExperienceProviderGateway::class)
            ->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $source);
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isLocked);
        // The 5000 XP was credited exactly once: level 2, currentXp 1000 (not 2 level-ups).
        self::assertSame(2, $reloaded->player->level);
        self::assertSame(1000, $reloaded->player->currentXp);
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
}
