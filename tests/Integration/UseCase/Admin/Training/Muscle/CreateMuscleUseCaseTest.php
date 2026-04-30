<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateMuscleUseCaseTest extends KernelTestCase
{
    public function testItCreatesAnMuscleAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $useCase = $container->get(CreateMuscleUseCase::class);
        $providerGateway = $container->get(MuscleProviderGateway::class);

        $output = $useCase->execute(new CreateMuscleDataInput('Test Barbell'));

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
        $useCase = self::getContainer()->get(CreateMuscleUseCase::class);

        try {
            $useCase->execute(new CreateMuscleDataInput(''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('label', $e->violations);
        }
    }

    public function testItRejectsAlreadyTakenLabel(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(CreateMuscleUseCase::class);

        $useCase->execute(new CreateMuscleDataInput('Duplicate Test'));

        try {
            $useCase->execute(new CreateMuscleDataInput('Duplicate Test'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another muscle already uses this label.', $e->violations['label'] ?? []);
        }
    }
}
