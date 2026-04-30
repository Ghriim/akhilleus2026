<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\ListEquipmentsDataInput;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use App\UseCase\Admin\Training\Equipment\ListEquipmentsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListEquipmentsUseCaseTest extends KernelTestCase
{
    public function testItReturnsEveryEquipmentSeededInTheTransaction(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateEquipmentUseCase::class);
        $listUseCase = $container->get(ListEquipmentsUseCase::class);

        $createUseCase->execute(new CreateEquipmentDataInput('List Item Alpha'));
        $createUseCase->execute(new CreateEquipmentDataInput('List Item Beta'));

        $list = $listUseCase->execute(new ListEquipmentsDataInput());

        self::assertGreaterThanOrEqual(2, count($list));
        $labels = array_map(static fn ($item) => $item->label, $list);
        self::assertContains('List Item Alpha', $labels);
        self::assertContains('List Item Beta', $labels);
    }
}
