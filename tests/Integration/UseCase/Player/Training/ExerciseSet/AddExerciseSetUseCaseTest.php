<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\AddExerciseSetDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\Player\Training\ExerciseSet\AddExerciseSetValidator;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Repository\Training\Workout\ExerciseRepository;
use App\Infrastructure\Repository\Training\Workout\ExerciseSetRepository;
use App\UseCase\Player\Training\ExerciseSet\AddExerciseSetUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AddExerciseSetUseCaseTest extends KernelTestCase
{
    use ExerciseSetTestSetupTrait;

    public function testItAddsASetWithPlannedValues(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-set-happy');
        [$exercise] = self::createTestExerciseWithSet($container, $player);

        $useCase = self::buildUseCase($container, $player);
        $output = $useCase->execute(new AddExerciseSetDataInput($exercise->id, plannedReps: 8, plannedWeight: '50.00'));

        self::assertSame($exercise->id, $output->exerciseId);
        self::assertSame(1, $output->position);
        self::assertSame(8, $output->plannedReps);
        self::assertSame('50.00', $output->plannedWeight);
        self::assertFalse($output->completed);
    }

    public function testItRejectsAPlannedFieldNotTrackedByMovement(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-set-tracking-mismatch');
        [$exercise] = self::createTestExerciseWithSet($container, $player, tracking: ['tracksRepetitions' => true]);

        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new AddExerciseSetDataInput($exercise->id, plannedReps: 5, plannedDistanceMeters: '1000'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddExerciseSetValidator::TRACKING_MISMATCH_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('plannedDistanceMeters', $e->violations);
        }
    }

    public function testItRejectsACompletedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-set-completed');
        [$exercise] = self::createTestExerciseWithSet($container, $player, WorkoutStatusRegistry::COMPLETED);

        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new AddExerciseSetDataInput($exercise->id, plannedReps: 5));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddExerciseSetValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItThrowsNotFoundForAnUnknownExercise(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-set-not-found');

        $useCase = self::buildUseCase($container, $player);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new AddExerciseSetDataInput('00000000000000000000000000'));
    }

    private static function buildUseCase(\Psr\Container\ContainerInterface $container, \App\Domain\DTO\DataModel\User\PlayerDataModel $player): AddExerciseSetUseCase
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $resolver = self::stubResolver($player);

        return new AddExerciseSetUseCase(
            new AddExerciseSetValidator($resolver),
            $resolver,
            new ExerciseRepository($registry),
            new ExerciseSetRepository($registry),
            new ExerciseSetPersister($em, $clock),
        );
    }
}
