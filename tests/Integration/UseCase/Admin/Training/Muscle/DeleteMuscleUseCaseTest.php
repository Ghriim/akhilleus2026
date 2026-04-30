<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\DeleteMuscleDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use App\UseCase\Admin\Training\Muscle\DeleteMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteMuscleUseCaseTest extends KernelTestCase
{
    public function testItDeletesAnExistingMuscle(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateMuscleUseCase::class);
        $deleteUseCase = $container->get(DeleteMuscleUseCase::class);
        $providerGateway = $container->get(MuscleProviderGateway::class);

        $created = $createUseCase->execute(new CreateMuscleDataInput('Delete Test'));

        $output = $deleteUseCase->execute(new DeleteMuscleDataInput($created->id));

        self::assertSame($created->id, $output->deletedId);
        self::assertNull($providerGateway->findOneForAdminDetails($created->id));
    }

    public function testItThrowsWhenIdDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(DeleteMuscleUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new DeleteMuscleDataInput('does-not-exist'));
    }
}
