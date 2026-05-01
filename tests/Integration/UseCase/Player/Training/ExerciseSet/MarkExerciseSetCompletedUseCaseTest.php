<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\MarkExerciseSetCompletedDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\Player\Training\ExerciseSet\MarkExerciseSetCompletedValidator;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Repository\Training\Workout\ExerciseSetRepository;
use App\UseCase\Player\Training\ExerciseSet\MarkExerciseSetCompletedUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MarkExerciseSetCompletedUseCaseTest extends KernelTestCase
{
    use ExerciseSetTestSetupTrait;

    public function testItMarksASetCompleted(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'mark-completed-happy');
        [, $set] = self::createTestExerciseWithSet($container, $player);

        $useCase = self::buildUseCase($container, $player);
        $output = $useCase->execute(new MarkExerciseSetCompletedDataInput($set->id));

        self::assertTrue($output->completed);
    }

    public function testItRejectsAPlannedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'mark-completed-planned-rejected');
        [, $set] = self::createTestExerciseWithSet($container, $player, WorkoutStatusRegistry::PLANNED);

        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new MarkExerciseSetCompletedDataInput($set->id));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(MarkExerciseSetCompletedValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    private static function buildUseCase(\Psr\Container\ContainerInterface $container, \App\Domain\DTO\DataModel\User\PlayerDataModel $player): MarkExerciseSetCompletedUseCase
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $resolver = self::stubResolver($player);

        return new MarkExerciseSetCompletedUseCase(
            new MarkExerciseSetCompletedValidator($resolver),
            $resolver,
            new ExerciseSetRepository($registry),
            new ExerciseSetPersister($em, $clock),
        );
    }
}
