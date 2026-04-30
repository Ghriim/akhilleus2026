<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\ListMovementsDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\UseCase\Admin\Training\Movement\CreateMovementUseCase;
use App\UseCase\Admin\Training\Movement\ListMovementsUseCase;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListMovementsUseCaseTest extends KernelTestCase
{
    public function testItReturnsMovementsSeededInTheTransaction(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createMuscle = $container->get(CreateMuscleUseCase::class);
        $createMovement = $container->get(CreateMovementUseCase::class);
        $listMovements = $container->get(ListMovementsUseCase::class);

        $main = $createMuscle->execute(new CreateMuscleDataInput('List Cardio'));
        $createMovement->execute(new CreateMovementDataInput(
            'List Movement Alpha',
            $main->id,
            [],
            [],
            true,
            false,
            false,
            false,
            false,
            false,
        ));
        $createMovement->execute(new CreateMovementDataInput(
            'List Movement Beta',
            $main->id,
            [],
            [],
            true,
            false,
            false,
            false,
            false,
            false,
        ));

        $list = $listMovements->execute(new ListMovementsDataInput());

        $labels = array_map(static fn ($item) => $item->label, $list);
        self::assertContains('List Movement Alpha', $labels);
        self::assertContains('List Movement Beta', $labels);
    }
}
