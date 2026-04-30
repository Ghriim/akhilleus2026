<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\GetMuscleDetailsDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use App\UseCase\Admin\Training\Muscle\GetMuscleDetailsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetMuscleDetailsUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheMuscleForAnExistingId(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateMuscleUseCase::class);
        $getUseCase = $container->get(GetMuscleDetailsUseCase::class);

        $created = $createUseCase->execute(new CreateMuscleDataInput('Detail Test'));

        $output = $getUseCase->execute(new GetMuscleDetailsDataInput($created->id));

        self::assertSame($created->id, $output->id);
        self::assertSame('Detail Test', $output->label);
        self::assertSame('detail-test', $output->slug);
    }

    public function testItThrowsWhenIdDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(GetMuscleDetailsUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new GetMuscleDetailsDataInput('does-not-exist'));
    }
}
