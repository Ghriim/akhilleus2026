<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\RemoveExerciseSetDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Validator\Player\Training\ExerciseSet\RemoveExerciseSetValidator;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Repository\Training\Workout\ExerciseSetRepository;
use App\UseCase\Player\Training\ExerciseSet\RemoveExerciseSetUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RemoveExerciseSetUseCaseTest extends KernelTestCase
{
    use ExerciseSetTestSetupTrait;

    public function testItRemovesAnExistingSet(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'remove-set-happy');
        [, $set] = self::createTestExerciseWithSet($container, $player);

        $useCase = self::buildUseCase($container, $player);
        $useCase->execute(new RemoveExerciseSetDataInput($set->id));

        $em = $container->get('doctrine.orm.entity_manager');
        self::assertNull($em->getRepository(ExerciseSetDataModel::class)->find($set->id));
    }

    public function testItThrowsNotFoundForAnUnknownSet(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'remove-set-not-found');

        $useCase = self::buildUseCase($container, $player);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new RemoveExerciseSetDataInput('00000000000000000000000000'));
    }

    private static function buildUseCase(\Psr\Container\ContainerInterface $container, \App\Domain\DTO\DataModel\User\PlayerDataModel $player): RemoveExerciseSetUseCase
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $resolver = self::stubResolver($player);

        return new RemoveExerciseSetUseCase(
            new RemoveExerciseSetValidator($resolver),
            $resolver,
            new ExerciseSetRepository($registry),
            new ExerciseSetPersister($em, $clock),
        );
    }
}
