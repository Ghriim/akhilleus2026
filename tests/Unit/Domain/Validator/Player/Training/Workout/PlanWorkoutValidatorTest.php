<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\PlanWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\PlanWorkoutValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[AllowMockObjectsWithoutExpectations]
final class PlanWorkoutValidatorTest extends TestCase
{
    private LoggedPlayerResolverInterface&MockObject $loggedPlayerResolver;
    private ClockInterface&MockObject $clock;
    private PlanWorkoutValidator $validator;

    protected function setUp(): void
    {
        $this->loggedPlayerResolver = $this->createMock(LoggedPlayerResolverInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->validator = new PlanWorkoutValidator($this->loggedPlayerResolver, $this->clock);
    }

    public function testItPassesForAFutureDate(): void
    {
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-04-30T12:00:00Z'));

        $this->validator->validate(new PlanWorkoutDataInput(new \DateTimeImmutable('2026-04-30T12:00:01Z')));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAPastDate(): void
    {
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-04-30T12:00:00Z'));

        try {
            $this->validator->validate(new PlanWorkoutDataInput(new \DateTimeImmutable('2026-04-29T23:59:59Z')));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(PlanWorkoutValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Planned date must be in the future.', $e->violations['plannedAt'] ?? []);
        }
    }

    public function testItRejectsTheCurrentInstant(): void
    {
        $now = new \DateTimeImmutable('2026-04-30T12:00:00Z');
        $this->clock->method('now')->willReturn($now);

        try {
            $this->validator->validate(new PlanWorkoutDataInput($now));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('plannedAt', $e->violations);
        }
    }
}
