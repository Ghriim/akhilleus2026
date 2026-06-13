<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\CreateLevelBracketDataInput;
use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Leveling\LevelBracket\CreateLevelBracketValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class CreateLevelBracketValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private LevelBracketProviderGateway&MockObject $levelBracketProvider;
    private CreateLevelBracketValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->levelBracketProvider = $this->createMock(LevelBracketProviderGateway::class);
        $this->validator = new CreateLevelBracketValidator($this->loggedUserResolver, $this->levelBracketProvider);
    }

    public function testItPassesWhenTheNewBracketCompletesAValidCurve(): void
    {
        $this->levelBracketProvider->method('findAllOrderedAsc')->willReturn([
            new LevelBracketDataModel(1, 10, 1000, 2, 0),
        ]);

        $this->validator->validate(new CreateLevelBracketDataInput(11, null, 3000, 2, 50000));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsInvalidFields(): void
    {
        $this->levelBracketProvider->method('findAllOrderedAsc')->willReturn([]);

        try {
            $this->validator->validate(new CreateLevelBracketDataInput(0, 5, 1000, 0, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateLevelBracketValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('fromLevel', $e->violations);
            self::assertArrayHasKey('exponentK', $e->violations);
        }
    }

    public function testItRejectsABracketThatBreaksTheCurve(): void
    {
        $this->levelBracketProvider->method('findAllOrderedAsc')->willReturn([
            new LevelBracketDataModel(1, 10, 1000, 2, 0),
            new LevelBracketDataModel(11, null, 3000, 2, 50000),
        ]);

        try {
            // Overlaps the existing 1-10 bracket.
            $this->validator->validate(new CreateLevelBracketDataInput(5, 8, 1000, 2, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateLevelBracketValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('curve', $e->violations);
        }
    }
}
