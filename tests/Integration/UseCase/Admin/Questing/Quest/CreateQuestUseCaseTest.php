<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\CreateQuestDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Validator\Admin\Questing\Quest\CreateQuestValidator;
use App\UseCase\Admin\Questing\Quest\CreateQuestUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateQuestUseCaseTest extends KernelTestCase
{
    public function testItCreatesAnAutomaticQuestAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $useCase = $container->get(CreateQuestUseCase::class);
        $output = $useCase->execute(new CreateQuestDataInput(
            'Hydrate 1.5L',
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::DAILY,
            120,
            QuestMetricRegistry::HYDRATION_ML_DAILY,
            '1500',
        ));

        self::assertNotEmpty($output->id);
        self::assertSame('Hydrate 1.5L', $output->label);
        self::assertSame(QuestKindRegistry::AUTOMATIC, $output->kind);
        self::assertSame(QuestMetricRegistry::HYDRATION_ML_DAILY, $output->metric);
        self::assertSame('1500', $output->targetValue);
        self::assertSame(120, $output->rewardedXp);
        self::assertNotNull($output->dateStart);
        self::assertNull($output->dateEnd);

        self::assertNotNull($container->get(QuestProviderGateway::class)->findOneByIdForAdminAction($output->id));
    }

    public function testItCreatesAManualQuestDefaultingTheStartDate(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $useCase = $container->get(CreateQuestUseCase::class);
        $output = $useCase->execute(new CreateQuestDataInput(
            'Weekly stretch',
            QuestKindRegistry::MANUAL,
            QuestPeriodicityRegistry::WEEKLY,
            60,
        ));

        self::assertSame(QuestKindRegistry::MANUAL, $output->kind);
        self::assertNull($output->metric);
        self::assertNull($output->targetValue);
        self::assertNotNull($output->dateStart);
    }

    public function testItRejectsAnAutomaticQuestWithoutAMetric(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $useCase = $container->get(CreateQuestUseCase::class);

        try {
            $useCase->execute(new CreateQuestDataInput(
                'No metric',
                QuestKindRegistry::AUTOMATIC,
                QuestPeriodicityRegistry::DAILY,
                100,
                null,
                '1500',
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::KIND_METRIC_MISMATCH_CODE, $e->errorCode);
            self::assertArrayHasKey('metric', $e->violations);
        }
    }
}
