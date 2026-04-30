<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\UpdateMuscleDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use App\UseCase\Admin\Training\Muscle\UpdateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateMuscleUseCaseTest extends KernelTestCase
{
    public function testItUpdatesTheLabelAndRecomputesTheSlug(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateMuscleUseCase::class);
        $updateUseCase = $container->get(UpdateMuscleUseCase::class);

        $created = $createUseCase->execute(new CreateMuscleDataInput('Update Test Original'));

        $updated = $updateUseCase->execute(new UpdateMuscleDataInput($created->id, 'Update Test Renamed'));

        self::assertSame($created->id, $updated->id);
        self::assertSame('Update Test Renamed', $updated->label);
        self::assertSame('update-test-renamed', $updated->slug);
    }

    public function testItThrowsWhenIdDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(UpdateMuscleUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new UpdateMuscleDataInput('does-not-exist', 'Whatever'));
    }

    public function testItRejectsLabelAlreadyUsedByAnotherRow(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateMuscleUseCase::class);
        $updateUseCase = $container->get(UpdateMuscleUseCase::class);

        $createUseCase->execute(new CreateMuscleDataInput('Update Conflict A'));
        $b = $createUseCase->execute(new CreateMuscleDataInput('Update Conflict B'));

        try {
            $updateUseCase->execute(new UpdateMuscleDataInput($b->id, 'Update Conflict A'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another muscle already uses this label.', $e->violations['label'] ?? []);
        }
    }
}
