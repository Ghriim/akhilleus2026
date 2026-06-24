<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\GetFrontThemeDetailsDataInput;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\UseCase\Admin\User\FrontTheme\GetFrontThemeDetailsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class GetFrontThemeDetailsUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheThemeDetails(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $theme = $container->get(FrontThemePersisterGateway::class)->create(new FrontThemeDataModel('Aurora'));
        $useCase = $container->get(GetFrontThemeDetailsUseCase::class);

        $output = $useCase->execute(new GetFrontThemeDetailsDataInput($theme->id));

        self::assertSame($theme->id, $output->id);
        self::assertSame('Aurora', $output->name);
    }

    public function testItThrowsWhenThemeDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(GetFrontThemeDetailsUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new GetFrontThemeDetailsDataInput((string) new Ulid()));
    }
}
