<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\CreateFrontThemeDataInput;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Validator\Admin\User\FrontTheme\CreateFrontThemeValidator;
use App\UseCase\Admin\User\FrontTheme\CreateFrontThemeUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateFrontThemeUseCaseTest extends KernelTestCase
{
    public function testItCreatesAThemeAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $useCase = $container->get(CreateFrontThemeUseCase::class);
        $provider = $container->get(FrontThemeProviderGateway::class);

        $output = $useCase->execute(new CreateFrontThemeDataInput('Aurora', 'A glowing theme.'));

        self::assertSame('Aurora', $output->name);
        self::assertSame('A glowing theme.', $output->description);
        self::assertNull($output->imagePreviewUrl);
        self::assertNotEmpty($output->id);

        $persisted = $provider->findOneByIdForAdminAction($output->id);
        self::assertNotNull($persisted);
        self::assertSame('Aurora', $persisted->name);
        self::assertNull($persisted->imageFilename);
    }

    public function testItRejectsAnEmptyName(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(CreateFrontThemeUseCase::class);

        try {
            $useCase->execute(new CreateFrontThemeDataInput('  '));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateFrontThemeValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('name', $e->violations);
        }
    }

    public function testItRejectsADuplicateName(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $container->get(FrontThemePersisterGateway::class)->create(new FrontThemeDataModel('System'));
        $useCase = $container->get(CreateFrontThemeUseCase::class);

        try {
            $useCase->execute(new CreateFrontThemeDataInput('System'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateFrontThemeValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Another theme already uses this name.', $e->violations['name']);
        }
    }
}
