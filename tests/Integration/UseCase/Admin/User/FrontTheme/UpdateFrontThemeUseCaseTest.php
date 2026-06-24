<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\UpdateFrontThemeDataInput;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\Domain\Validator\Admin\User\FrontTheme\UpdateFrontThemeValidator;
use App\UseCase\Admin\User\FrontTheme\UpdateFrontThemeUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class UpdateFrontThemeUseCaseTest extends KernelTestCase
{
    public function testItUpdatesNameAndDescription(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $theme = $container->get(FrontThemePersisterGateway::class)->create(new FrontThemeDataModel('Old'));
        $useCase = $container->get(UpdateFrontThemeUseCase::class);

        $output = $useCase->execute(new UpdateFrontThemeDataInput($theme->id, 'New', 'Updated.'));

        self::assertSame('New', $output->name);
        self::assertSame('Updated.', $output->description);
    }

    public function testItAllowsKeepingItsOwnName(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $theme = $container->get(FrontThemePersisterGateway::class)->create(new FrontThemeDataModel('Stable'));
        $useCase = $container->get(UpdateFrontThemeUseCase::class);

        $output = $useCase->execute(new UpdateFrontThemeDataInput($theme->id, 'Stable', 'Same name, new description.'));

        self::assertSame('Stable', $output->name);
        self::assertSame('Same name, new description.', $output->description);
    }

    public function testItThrowsWhenThemeDoesNotExist(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(UpdateFrontThemeUseCase::class);

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new UpdateFrontThemeDataInput((string) new Ulid(), 'Whatever'));
    }

    public function testItRejectsANameUsedByAnotherTheme(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $persister = $container->get(FrontThemePersisterGateway::class);
        $persister->create(new FrontThemeDataModel('Basic'));
        $target = $persister->create(new FrontThemeDataModel('System'));
        $useCase = $container->get(UpdateFrontThemeUseCase::class);

        try {
            $useCase->execute(new UpdateFrontThemeDataInput($target->id, 'Basic'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateFrontThemeValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('name', $e->violations);
        }
    }
}
