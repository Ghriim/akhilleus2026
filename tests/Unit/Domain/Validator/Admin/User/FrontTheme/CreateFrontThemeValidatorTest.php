<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\CreateFrontThemeDataInput;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\User\FrontTheme\CreateFrontThemeValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class CreateFrontThemeValidatorTest extends TestCase
{
    private FrontThemeProviderGateway&MockObject $provider;
    private CreateFrontThemeValidator $validator;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(FrontThemeProviderGateway::class);
        $this->validator = new CreateFrontThemeValidator(
            $this->createMock(LoggedUserResolverInterface::class),
            $this->provider,
        );
    }

    public function testItPassesForAUniqueName(): void
    {
        $this->provider->method('findOneByNameForUniqueness')->willReturn(null);

        $this->validator->validate(new CreateFrontThemeDataInput('Aurora'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnEmptyName(): void
    {
        $this->provider->expects(self::never())->method('findOneByNameForUniqueness');

        try {
            $this->validator->validate(new CreateFrontThemeDataInput('   '));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateFrontThemeValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Name must not be empty.', $e->violations['name'] ?? []);
        }
    }

    public function testItRejectsADuplicateName(): void
    {
        $this->provider
            ->expects(self::once())
            ->method('findOneByNameForUniqueness')
            ->with('System')
            ->willReturn(new FrontThemeDataModel('System'));

        try {
            $this->validator->validate(new CreateFrontThemeDataInput('System'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another theme already uses this name.', $e->violations['name'] ?? []);
        }
    }
}
