<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\ListFrontThemesDataInput;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\UseCase\Admin\User\FrontTheme\ListFrontThemesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListFrontThemesUseCaseTest extends KernelTestCase
{
    public function testItListsThemesOrderedByName(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $persister = $container->get(FrontThemePersisterGateway::class);
        $persister->create(new FrontThemeDataModel('System'));
        $persister->create(new FrontThemeDataModel('Basic'));
        $useCase = $container->get(ListFrontThemesUseCase::class);

        $outputs = $useCase->execute(new ListFrontThemesDataInput());

        self::assertCount(2, $outputs);
        self::assertSame('Basic', $outputs[0]->name);
        self::assertSame('System', $outputs[1]->name);
        self::assertNull($outputs[0]->imagePreviewUrl);
    }
}
