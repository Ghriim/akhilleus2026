<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\Player\Questing\ClaimQuestRewardDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\Questing\QuestProgression\QuestProgressionPersisterGateway;
use App\Domain\Gateway\Provider\Questing\QuestProgression\QuestProgressionProviderGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Service\Questing\QuestProgressionFactory;
use App\Domain\Validator\Player\Questing\ClaimQuestRewardValidator;
use App\UseCase\Player\Questing\ClaimQuestRewardUseCase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ClaimQuestRewardUseCaseTest extends KernelTestCase
{
    use QuestingPlayerTestTrait;

    public function testItClaimsAClaimableQuestAndGrantsExperience(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'claim-quest-ok');
        $quest = self::seedQuest($container, QuestKindRegistry::MANUAL, QuestPeriodicityRegistry::DAILY, rewardedXp: 250);
        // A MANUAL quest's progression is CLAIMABLE on first materialisation.
        $progression = $container->get(QuestProgressionFactory::class)
            ->findOrCreate($quest, $player, $container->get(ClockInterface::class)->now());

        $output = self::buildUseCase($container, $player)->execute(new ClaimQuestRewardDataInput($progression->id));

        self::assertSame($progression->id, $output->progressionId);
        self::assertSame(250, $output->amount);
        self::assertNotEmpty($output->earnedExperienceId);

        $em = $container->get('doctrine.orm.entity_manager');
        $earned = $em->find(EarnedExperienceDataModel::class, $output->earnedExperienceId);
        self::assertNotNull($earned);
        self::assertSame(250, $earned->amount);
        self::assertSame(EarnedExperienceSourceTypeRegistry::QUEST, $earned->sourceType);
        self::assertSame($progression->id, $earned->sourceId);

        $reloaded = $container->get(QuestProgressionProviderGateway::class)
            ->findOneByIdForPlayerAction($progression->id, $player);
        self::assertNotNull($reloaded);
        self::assertSame(QuestProgressionStatusRegistry::REWARDED, $reloaded->status);
        self::assertNotNull($reloaded->claimedDate);
    }

    public function testItThrowsWhenTheProgressionDoesNotExist(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'claim-quest-404');
        $useCase = self::buildUseCase($container, $player);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new ClaimQuestRewardDataInput('01HZX000000000000000000404'));
    }

    public function testItRejectsClaimingAnInProgressAutomaticQuest(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'claim-quest-in-progress');
        $quest = self::seedQuest(
            $container,
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::DAILY,
            QuestMetricRegistry::STEPS_DAILY,
            '10000',
        );
        // An AUTOMATIC quest's progression starts IN_PROGRESS — not claimable yet.
        $progression = $container->get(QuestProgressionFactory::class)
            ->findOrCreate($quest, $player, $container->get(ClockInterface::class)->now());

        try {
            self::buildUseCase($container, $player)->execute(new ClaimQuestRewardDataInput($progression->id));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ClaimQuestRewardValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ClaimQuestRewardUseCase
    {
        return new ClaimQuestRewardUseCase(
            new ClaimQuestRewardValidator(),
            self::stubResolver($player),
            $container->get(QuestProgressionProviderGateway::class),
            $container->get(QuestProgressionPersisterGateway::class),
            $container->get(EarnedExperiencePersisterGateway::class),
            $container->get(ClockInterface::class),
        );
    }
}
