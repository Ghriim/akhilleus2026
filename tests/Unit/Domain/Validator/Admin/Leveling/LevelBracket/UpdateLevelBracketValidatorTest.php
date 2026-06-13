<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\UpdateLevelBracketDataInput;
use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Leveling\LevelBracket\UpdateLevelBracketValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateLevelBracketValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private LevelBracketProviderGateway&MockObject $levelBracketProvider;
    private UpdateLevelBracketValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->levelBracketProvider = $this->createMock(LevelBracketProviderGateway::class);
        $this->validator = new UpdateLevelBracketValidator($this->loggedUserResolver, $this->levelBracketProvider);
    }

    /**
     * @return list<LevelBracketDataModel>
     */
    private function seededCurve(): array
    {
        $first = new LevelBracketDataModel(1, 10, 1000, 2, 0);
        $first->id = 'bracket-1';
        $second = new LevelBracketDataModel(11, null, 3000, 2, 50000);
        $second->id = 'bracket-2';

        return [$first, $second];
    }

    public function testItPassesWhenTheEditKeepsTheCurveValid(): void
    {
        $this->levelBracketProvider->method('findAllOrderedAsc')->willReturn($this->seededCurve());

        // Re-tune bracket-2's coefficient only; the curve structure is unchanged.
        $this->validator->validate(new UpdateLevelBracketDataInput('bracket-2', 11, null, 9000, 2, 50000));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnEditThatBreaksTheCurve(): void
    {
        $this->levelBracketProvider->method('findAllOrderedAsc')->willReturn($this->seededCurve());

        try {
            // Move the first bracket off level 1.
            $this->validator->validate(new UpdateLevelBracketDataInput('bracket-1', 5, 10, 1000, 2, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateLevelBracketValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('curve', $e->violations);
        }
    }

    public function testItRejectsInvalidFields(): void
    {
        $this->levelBracketProvider->method('findAllOrderedAsc')->willReturn($this->seededCurve());

        try {
            $this->validator->validate(new UpdateLevelBracketDataInput('bracket-2', 11, 5, 3000, 2, 50000));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateLevelBracketValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('toLevel', $e->violations);
        }
    }
}
