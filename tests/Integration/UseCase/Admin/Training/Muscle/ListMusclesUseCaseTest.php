<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\ListMusclesDataInput;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use App\UseCase\Admin\Training\Muscle\ListMusclesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListMusclesUseCaseTest extends KernelTestCase
{
    public function testItReturnsEveryMuscleSeededInTheTransaction(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $createUseCase = $container->get(CreateMuscleUseCase::class);
        $listUseCase = $container->get(ListMusclesUseCase::class);

        $createUseCase->execute(new CreateMuscleDataInput('List Item Alpha'));
        $createUseCase->execute(new CreateMuscleDataInput('List Item Beta'));

        $list = $listUseCase->execute(new ListMusclesDataInput());

        self::assertGreaterThanOrEqual(2, count($list));
        $labels = array_map(static fn ($item) => $item->label, $list);
        self::assertContains('List Item Alpha', $labels);
        self::assertContains('List Item Beta', $labels);
    }
}
