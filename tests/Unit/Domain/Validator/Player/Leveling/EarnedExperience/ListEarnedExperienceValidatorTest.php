<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Leveling\EarnedExperience;

use App\Domain\DTO\DataInput\Player\Leveling\EarnedExperience\ListEarnedExperienceDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Player\Leveling\EarnedExperience\ListEarnedExperienceValidator;
use PHPUnit\Framework\TestCase;

final class ListEarnedExperienceValidatorTest extends TestCase
{
    private ListEarnedExperienceValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListEarnedExperienceValidator();
    }

    public function testItPassesForAValidQuery(): void
    {
        $this->validator->validate(new ListEarnedExperienceDataInput(1, 20));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAPageBelowOne(): void
    {
        $this->assertRejected(new ListEarnedExperienceDataInput(0, 20), 'page');
    }

    public function testItRejectsAPerPageBelowOne(): void
    {
        $this->assertRejected(new ListEarnedExperienceDataInput(1, 0), 'perPage');
    }

    public function testItRejectsAPerPageAboveTheMaximum(): void
    {
        $this->assertRejected(new ListEarnedExperienceDataInput(1, ListEarnedExperienceDataInput::MAX_PER_PAGE + 1), 'perPage');
    }

    public function testItAccumulatesViolationsAcrossFields(): void
    {
        try {
            $this->validator->validate(new ListEarnedExperienceDataInput(0, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListEarnedExperienceValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('page', $e->violations);
            self::assertArrayHasKey('perPage', $e->violations);
        }
    }

    private function assertRejected(ListEarnedExperienceDataInput $input, string $expectedKey): void
    {
        try {
            $this->validator->validate($input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListEarnedExperienceValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey($expectedKey, $e->violations);
        }
    }
}
