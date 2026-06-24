<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\UpdateFrontThemeDataInput;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\User\FrontTheme\UpdateFrontThemeValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateFrontThemeValidatorTest extends TestCase
{
    private FrontThemeProviderGateway&MockObject $provider;
    private UpdateFrontThemeValidator $validator;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(FrontThemeProviderGateway::class);
        $this->validator = new UpdateFrontThemeValidator(
            $this->createMock(LoggedUserResolverInterface::class),
            $this->provider,
        );
    }

    private function theme(string $id): FrontThemeDataModel
    {
        $theme = new FrontThemeDataModel('Current');
        $theme->id = $id;

        return $theme;
    }

    public function testItPassesForAUniqueName(): void
    {
        $this->provider->method('findOneByNameForUniqueness')->willReturn(null);

        $this->validator->validate(new UpdateFrontThemeDataInput('id-1', 'Aurora'), $this->theme('id-1'));

        $this->expectNotToPerformAssertions();
    }

    public function testItAllowsTheThemeToKeepItsOwnName(): void
    {
        $current = $this->theme('id-1');
        $this->provider->method('findOneByNameForUniqueness')->willReturn($current);

        $this->validator->validate(new UpdateFrontThemeDataInput('id-1', 'Current'), $current);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnEmptyName(): void
    {
        try {
            $this->validator->validate(new UpdateFrontThemeDataInput('id-1', ' '), $this->theme('id-1'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateFrontThemeValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Name must not be empty.', $e->violations['name'] ?? []);
        }
    }

    public function testItRejectsANameOwnedByAnotherTheme(): void
    {
        $other = $this->theme('id-2');
        $this->provider->method('findOneByNameForUniqueness')->willReturn($other);

        try {
            $this->validator->validate(new UpdateFrontThemeDataInput('id-1', 'Taken'), $this->theme('id-1'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another theme already uses this name.', $e->violations['name'] ?? []);
        }
    }
}
