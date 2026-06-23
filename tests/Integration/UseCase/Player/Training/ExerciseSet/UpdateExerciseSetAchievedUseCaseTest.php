<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetAchievedDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\Player\Training\ExerciseSet\UpdateExerciseSetAchievedValidator;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Repository\Training\Workout\ExerciseSetRepository;
use App\UseCase\Player\Training\ExerciseSet\UpdateExerciseSetAchievedUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateExerciseSetAchievedUseCaseTest extends KernelTestCase
{
    use ExerciseSetTestSetupTrait;

    public function testItRecordsAchievedValuesOnAnInProgressWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'achieved-happy');
        [, $set] = self::createTestExerciseWithSet($container, $player);

        $useCase = self::buildUseCase($container, $player);
        $output = $useCase->execute(new UpdateExerciseSetAchievedDataInput($set->id, achievedReps: 7, achievedWeight: '47.50'));

        self::assertSame(7, $output->achievedReps);
        self::assertSame('47.50', $output->achievedWeight);
    }

    public function testItRejectsAPlannedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'achieved-planned-rejected');
        [, $set] = self::createTestExerciseWithSet($container, $player, WorkoutStatusRegistry::PLANNED);

        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new UpdateExerciseSetAchievedDataInput($set->id, achievedReps: 7));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateExerciseSetAchievedValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    private static function buildUseCase(\Psr\Container\ContainerInterface $container, \App\Domain\DTO\DataModel\User\PlayerDataModel $player): UpdateExerciseSetAchievedUseCase
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $resolver = self::stubResolver($player);

        return new UpdateExerciseSetAchievedUseCase(
            new UpdateExerciseSetAchievedValidator($resolver),
            $resolver,
            new ExerciseSetRepository($registry),
            new ExerciseSetPersister($em, $clock),
            self::getContainer()->get(ObjectMapperInterface::class),
        );
    }
}
