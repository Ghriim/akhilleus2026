<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\UpdateEquipmentDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use App\UseCase\Admin\Training\Equipment\UpdateEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateEquipmentUseCaseTest extends KernelTestCase
{
    public function testItUpdatesTheLabelAndRecomputesTheSlug(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateEquipmentUseCase::class);
        $updateUseCase = $container->get(UpdateEquipmentUseCase::class);

        $created = $createUseCase->execute(new CreateEquipmentDataInput('Update Test Original'));

        $updated = $updateUseCase->execute(new UpdateEquipmentDataInput($created->id, 'Update Test Renamed'));

        self::assertSame($created->id, $updated->id);
        self::assertSame('Update Test Renamed', $updated->label);
        self::assertSame('update-test-renamed', $updated->slug);
    }

    public function testItThrowsWhenIdDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(UpdateEquipmentUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new UpdateEquipmentDataInput('does-not-exist', 'Whatever'));
    }

    public function testItRejectsLabelAlreadyUsedByAnotherRow(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateEquipmentUseCase::class);
        $updateUseCase = $container->get(UpdateEquipmentUseCase::class);

        $createUseCase->execute(new CreateEquipmentDataInput('Update Conflict A'));
        $b = $createUseCase->execute(new CreateEquipmentDataInput('Update Conflict B'));

        try {
            $updateUseCase->execute(new UpdateEquipmentDataInput($b->id, 'Update Conflict A'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another equipment already uses this label.', $e->violations['label'] ?? []);
        }
    }
}
