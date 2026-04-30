<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\DeleteMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\UseCase\Admin\Training\Movement\CreateMovementUseCase;
use App\UseCase\Admin\Training\Movement\DeleteMovementUseCase;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteMovementUseCaseTest extends KernelTestCase
{
    public function testItDeletesAnExistingMovement(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createMuscle = $container->get(CreateMuscleUseCase::class);
        $createMovement = $container->get(CreateMovementUseCase::class);
        $deleteMovement = $container->get(DeleteMovementUseCase::class);
        $movementProvider = $container->get(MovementProviderGateway::class);

        $main = $createMuscle->execute(new CreateMuscleDataInput('Delete Cardio'));
        $created = $createMovement->execute(new CreateMovementDataInput(
            'Delete Test Movement',
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

        $output = $deleteMovement->execute(new DeleteMovementDataInput($created->id));

        self::assertSame($created->id, $output->deletedId);
        self::assertNull($movementProvider->findOneForAdminDetails($created->id));
    }

    public function testItThrowsWhenIdDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(DeleteMovementUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new DeleteMovementDataInput('does-not-exist'));
    }
}
