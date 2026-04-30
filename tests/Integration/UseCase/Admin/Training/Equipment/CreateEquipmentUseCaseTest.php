<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateEquipmentUseCaseTest extends KernelTestCase
{
    public function testItCreatesAnEquipmentAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $useCase = $container->get(CreateEquipmentUseCase::class);
        $providerGateway = $container->get(EquipmentProviderGateway::class);

        $output = $useCase->execute(new CreateEquipmentDataInput('Test Barbell'));

        self::assertSame('Test Barbell', $output->label);
        self::assertSame('test-barbell', $output->slug);
        self::assertNotEmpty($output->id);

        $persisted = $providerGateway->findOneBySlugForUniqueness('test-barbell');
        self::assertNotNull($persisted);
        self::assertSame($output->id, $persisted->id);
    }

    public function testItRejectsEmptyLabel(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(CreateEquipmentUseCase::class);

        try {
            $useCase->execute(new CreateEquipmentDataInput(''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('label', $e->violations);
        }
    }

    public function testItRejectsAlreadyTakenLabel(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(CreateEquipmentUseCase::class);

        $useCase->execute(new CreateEquipmentDataInput('Duplicate Test'));

        try {
            $useCase->execute(new CreateEquipmentDataInput('Duplicate Test'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another equipment already uses this label.', $e->violations['label'] ?? []);
        }
    }
}
