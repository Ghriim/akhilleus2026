<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\DeleteFrontThemeDataInput;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\UseCase\Admin\User\FrontTheme\DeleteFrontThemeUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class DeleteFrontThemeUseCaseTest extends KernelTestCase
{
    public function testItDeletesAnExistingTheme(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $theme = $container->get(FrontThemePersisterGateway::class)->create(new FrontThemeDataModel('Doomed'));
        $useCase = $container->get(DeleteFrontThemeUseCase::class);
        $provider = $container->get(FrontThemeProviderGateway::class);

        $useCase->execute(new DeleteFrontThemeDataInput($theme->id));

        self::assertNull($provider->findOneByIdForAdminAction($theme->id));
    }

    public function testItThrowsWhenThemeDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(DeleteFrontThemeUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new DeleteFrontThemeDataInput((string) new Ulid()));
    }
}
