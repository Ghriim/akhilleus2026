<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetPlannedDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\Player\Training\ExerciseSet\UpdateExerciseSetPlannedValidator;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Repository\Training\Workout\ExerciseSetRepository;
use App\UseCase\Player\Training\ExerciseSet\UpdateExerciseSetPlannedUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateExerciseSetPlannedUseCaseTest extends KernelTestCase
{
    use ExerciseSetTestSetupTrait;

    public function testItUpdatesPlannedValues(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'update-planned-happy');
        [, $set] = self::createTestExerciseWithSet($container, $player, WorkoutStatusRegistry::PLANNED);

        $useCase = self::buildUseCase($container, $player);
        $output = $useCase->execute(new UpdateExerciseSetPlannedDataInput($set->id, plannedReps: 12, plannedWeight: '55.50'));

        self::assertSame(12, $output->plannedReps);
        self::assertSame('55.50', $output->plannedWeight);
    }

    public function testItRejectsAnInProgressWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'update-planned-in-progress');
        [, $set] = self::createTestExerciseWithSet($container, $player, WorkoutStatusRegistry::IN_PROGRESS);

        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new UpdateExerciseSetPlannedDataInput($set->id, plannedReps: 12));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateExerciseSetPlannedValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
        }
    }

    public function testItThrowsNotFoundForAnUnknownSet(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'update-planned-not-found');

        $useCase = self::buildUseCase($container, $player);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new UpdateExerciseSetPlannedDataInput('00000000000000000000000000'));
    }

    private static function buildUseCase(\Psr\Container\ContainerInterface $container, \App\Domain\DTO\DataModel\User\PlayerDataModel $player): UpdateExerciseSetPlannedUseCase
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $resolver = self::stubResolver($player);

        return new UpdateExerciseSetPlannedUseCase(
            new UpdateExerciseSetPlannedValidator($resolver),
            $resolver,
            new ExerciseSetRepository($registry),
            new ExerciseSetPersister($em, $clock),
            self::getContainer()->get(ObjectMapperInterface::class),
        );
    }
}
