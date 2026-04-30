<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use App\UseCase\Admin\Training\Movement\CreateMovementUseCase;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateMovementUseCaseTest extends KernelTestCase
{
    public function testItCreatesAMovementAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createMuscle = $container->get(CreateMuscleUseCase::class);
        $createEquipment = $container->get(CreateEquipmentUseCase::class);
        $createMovement = $container->get(CreateMovementUseCase::class);

        $main = $createMuscle->execute(new CreateMuscleDataInput('Test Chest'));
        $secondary = $createMuscle->execute(new CreateMuscleDataInput('Test Triceps'));
        $equipment = $createEquipment->execute(new CreateEquipmentDataInput('Test Barbell'));

        $output = $createMovement->execute(new CreateMovementDataInput(
            'Test Bench Press',
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

        self::assertSame('Test Bench Press', $output->label);
        self::assertSame('test-bench-press', $output->slug);
        self::assertSame($main->id, $output->mainMuscle->id);
        self::assertCount(1, $output->secondaryMuscles);
        self::assertCount(1, $output->equipments);
        self::assertTrue($output->tracksRepetitions);
    }

    public function testItRejectsEmptyLabelAndNoTrackingFields(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createMuscle = $container->get(CreateMuscleUseCase::class);
        $createMovement = $container->get(CreateMovementUseCase::class);

        $main = $createMuscle->execute(new CreateMuscleDataInput('Test Cardio'));

        try {
            $createMovement->execute(new CreateMovementDataInput(
                '',
                $main->id,
                [],
                [],
                false,
                false,
                false,
                false,
                false,
                false,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('label', $e->violations);
            self::assertArrayHasKey('tracking', $e->violations);
        }
    }

    public function testItRejectsUnknownMainMuscle(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(CreateMovementUseCase::class);

        try {
            $useCase->execute(new CreateMovementDataInput(
                'Ghost movement',
                'ghost-id',
                [],
                [],
                true,
                false,
                false,
                false,
                false,
                false,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('mainMuscleId', $e->violations);
        }
    }
}
