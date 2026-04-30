<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\GetEquipmentDetailsDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use App\UseCase\Admin\Training\Equipment\GetEquipmentDetailsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetEquipmentDetailsUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheEquipmentForAnExistingId(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateEquipmentUseCase::class);
        $getUseCase = $container->get(GetEquipmentDetailsUseCase::class);

        $created = $createUseCase->execute(new CreateEquipmentDataInput('Detail Test'));

        $output = $getUseCase->execute(new GetEquipmentDetailsDataInput($created->id));

        self::assertSame($created->id, $output->id);
        self::assertSame('Detail Test', $output->label);
        self::assertSame('detail-test', $output->slug);
    }

    public function testItThrowsWhenIdDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(GetEquipmentDetailsUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new GetEquipmentDetailsDataInput('does-not-exist'));
    }
}
