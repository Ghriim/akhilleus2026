<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\UpdateMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use App\UseCase\Admin\Training\Movement\CreateMovementUseCase;
use App\UseCase\Admin\Training\Movement\UpdateMovementUseCase;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateMovementUseCaseTest extends KernelTestCase
{
    public function testItUpdatesLabelAndAssociations(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createMuscle = $container->get(CreateMuscleUseCase::class);
        $createEquipment = $container->get(CreateEquipmentUseCase::class);
        $createMovement = $container->get(CreateMovementUseCase::class);
        $updateMovement = $container->get(UpdateMovementUseCase::class);

        $main = $createMuscle->execute(new CreateMuscleDataInput('Update Chest'));
        $newMain = $createMuscle->execute(new CreateMuscleDataInput('Update Quads'));
        $equipment = $createEquipment->execute(new CreateEquipmentDataInput('Update Barbell'));

        $created = $createMovement->execute(new CreateMovementDataInput(
            'Update Original Movement',
            $main->id,
            [],
            [$equipment->id],
            true,
            true,
            false,
            false,
            false,
            false,
        ));

        $updated = $updateMovement->execute(new UpdateMovementDataInput(
            $created->id,
            'Update Renamed Movement',
            $newMain->id,
            [],
            [],
            false,
            false,
            true,
            false,
            false,
            false,
        ));

        self::assertSame($created->id, $updated->id);
        self::assertSame('Update Renamed Movement', $updated->label);
        self::assertSame('update-renamed-movement', $updated->slug);
        self::assertSame($newMain->id, $updated->mainMuscle->id);
        self::assertCount(0, $updated->equipments);
        self::assertTrue($updated->tracksDuration);
        self::assertFalse($updated->tracksRepetitions);
    }

    public function testItThrowsWhenMovementDoesNotExist(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createMuscle = $container->get(CreateMuscleUseCase::class);
        $updateMovement = $container->get(UpdateMovementUseCase::class);

        $main = $createMuscle->execute(new CreateMuscleDataInput('Update Cardio'));

        $this->expectException(EntityNotFoundException::class);
        $updateMovement->execute(new UpdateMovementDataInput(
            'does-not-exist',
            'Whatever',
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
    }
}
