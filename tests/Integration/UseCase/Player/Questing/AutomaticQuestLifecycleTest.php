<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\Player\Questing\ClaimQuestRewardDataInput;
use App\Domain\DTO\DataInput\Player\Questing\ListQuestsDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\AddHydrationEntryDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\Questing\QuestProgression\QuestProgressionPersisterGateway;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Gateway\Provider\Questing\QuestProgression\QuestProgressionProviderGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Service\Questing\QuestProgressionFactory;
use App\Domain\Validator\Player\Questing\ClaimQuestRewardValidator;
use App\Domain\Validator\Player\Tracking\Hydration\AddHydrationEntryValidator;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationDailySummaryPersister;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationEntryPersister;
use App\Infrastructure\Repository\Tracking\Hydration\HydrationDailySummaryRepository;
use App\UseCase\Player\Questing\ClaimQuestRewardUseCase;
use App\UseCase\Player\Questing\ListDailyQuestsUseCase;
use App\UseCase\Player\Tracking\Hydration\AddHydrationEntryUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * End-to-end: an AUTOMATIC daily hydration quest auto-progresses as the player logs hydration
 * (the tracking write triggers QuestProgressionEvaluator, wired in 4.4), flips to CLAIMABLE once the
 * target is met, and grants an EarnedExperience when claimed.
 */
final class AutomaticQuestLifecycleTest extends KernelTestCase
{
    use QuestingPlayerTestTrait;

    public function testHydrationQuestProgressesToClaimableThenRewards(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'quest-lifecycle');
        $quest = self::seedQuest(
            $container,
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::DAILY,
            QuestMetricRegistry::HYDRATION_ML_DAILY,
            '1000',
            200,
        );

        $clock = $container->get(ClockInterface::class);
        $now = $clock->now();

        // 1. Log hydration totalling 1500 mL today → each write refreshes the quest progression.
        $addHydration = self::buildAddHydrationUseCase($container, $player);
        $addHydration->execute(new AddHydrationEntryDataInput($now, 600));
        $addHydration->execute(new AddHydrationEntryDataInput($now, 600));
        $addHydration->execute(new AddHydrationEntryDataInput($now, 300));

        // 2. The daily listing reflects a CLAIMABLE progression at 1500 / 1000.
        $listDaily = new ListDailyQuestsUseCase(
            self::stubResolver($player),
            $container->get(QuestProviderGateway::class),
            $container->get(QuestProgressionFactory::class),
            $clock,
            self::getContainer()->get(ObjectMapperInterface::class),
        );
        $items = $listDaily->execute(new ListQuestsDataInput());

        $progressionId = null;
        foreach ($items as $item) {
            if ($item->questId === $quest->id) {
                self::assertSame(QuestProgressionStatusRegistry::CLAIMABLE, $item->status);
                self::assertSame('1500.0000', $item->currentValue);
                $progressionId = $item->id;
            }
        }
        self::assertNotNull($progressionId, 'the hydration quest progression was listed');

        // 3. Claim the reward → REWARDED + an EarnedExperience row of the quest's rewardedXp.
        $claim = new ClaimQuestRewardUseCase(
            new ClaimQuestRewardValidator(),
            self::stubResolver($player),
            $container->get(QuestProgressionProviderGateway::class),
            $container->get(QuestProgressionPersisterGateway::class),
            $container->get(EarnedExperiencePersisterGateway::class),
            $clock,
            self::getContainer()->get(ObjectMapperInterface::class),
        );
        $claimOutput = $claim->execute(new ClaimQuestRewardDataInput($progressionId));

        self::assertSame(200, $claimOutput->amount);

        $reloaded = $container->get(QuestProgressionProviderGateway::class)
            ->findOneByIdForPlayerAction($progressionId, $player);
        self::assertNotNull($reloaded);
        self::assertSame(QuestProgressionStatusRegistry::REWARDED, $reloaded->status);

        $earned = $container->get('doctrine.orm.entity_manager')
            ->find(EarnedExperienceDataModel::class, $claimOutput->earnedExperienceId);
        self::assertNotNull($earned);
        self::assertSame(200, $earned->amount);
        self::assertSame(EarnedExperienceSourceTypeRegistry::QUEST, $earned->sourceType);
        self::assertSame($progressionId, $earned->sourceId);
    }

    private static function buildAddHydrationUseCase(
        ContainerInterface $container,
        PlayerDataModel $player,
    ): AddHydrationEntryUseCase {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);

        return new AddHydrationEntryUseCase(
            new AddHydrationEntryValidator($resolver),
            $resolver,
            new HydrationDailySummaryRepository($registry),
            $summaryPersister,
            new HydrationEntryPersister($em, $clock, $summaryPersister),
            $container->get(QuestProgressionEvaluator::class),
            $clock,
            self::getContainer()->get(ObjectMapperInterface::class),
        );
    }
}
