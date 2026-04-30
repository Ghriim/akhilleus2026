<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\GetMovementDetailsDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use App\UseCase\Admin\Training\Movement\CreateMovementUseCase;
use App\UseCase\Admin\Training\Movement\GetMovementDetailsUseCase;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetMovementDetailsUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheFullMovementDetails(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createMuscle = $container->get(CreateMuscleUseCase::class);
        $createEquipment = $container->get(CreateEquipmentUseCase::class);
        $createMovement = $container->get(CreateMovementUseCase::class);
        $getMovement = $container->get(GetMovementDetailsUseCase::class);

        $main = $createMuscle->execute(new CreateMuscleDataInput('Detail Chest'));
        $secondary = $createMuscle->execute(new CreateMuscleDataInput('Detail Triceps'));
        $equipment = $createEquipment->execute(new CreateEquipmentDataInput('Detail Barbell'));
        $created = $createMovement->execute(new CreateMovementDataInput(
            'Detail Test Movement',
            $main->id,
            [$secondary->id],
            [$equipment->id],
            true,
            true,
            false,
            false,
            false,
            false,
        ));

        $output = $getMovement->execute(new GetMovementDetailsDataInput($created->id));

        self::assertSame($created->id, $output->id);
        self::assertSame('Detail Test Movement', $output->label);
        self::assertSame($main->id, $output->mainMuscle->id);
        self::assertCount(1, $output->secondaryMuscles);
        self::assertCount(1, $output->equipments);
    }

    public function testItThrowsWhenIdDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(GetMovementDetailsUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new GetMovementDetailsDataInput('does-not-exist'));
    }
}
