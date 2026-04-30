<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\DeleteEquipmentDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use App\UseCase\Admin\Training\Equipment\DeleteEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteEquipmentUseCaseTest extends KernelTestCase
{
    public function testItDeletesAnExistingEquipment(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateEquipmentUseCase::class);
        $deleteUseCase = $container->get(DeleteEquipmentUseCase::class);
        $providerGateway = $container->get(EquipmentProviderGateway::class);

        $created = $createUseCase->execute(new CreateEquipmentDataInput('Delete Test'));

        $output = $deleteUseCase->execute(new DeleteEquipmentDataInput($created->id));

        self::assertSame($created->id, $output->deletedId);
        self::assertNull($providerGateway->findOneForAdminDetails($created->id));
    }

    public function testItThrowsWhenIdDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(DeleteEquipmentUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new DeleteEquipmentDataInput('does-not-exist'));
    }
}
