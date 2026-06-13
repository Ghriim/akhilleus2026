<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\LogSleepDataInput;
use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Sleep\LogSleepValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class LogSleepValidatorTest extends TestCase
{
    private SleepDailyEntryProviderGateway&MockObject $sleepProvider;
    private LogSleepValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->sleepProvider = $this->createMock(SleepDailyEntryProviderGateway::class);
        $this->validator = new LogSleepValidator(
            $this->createMock(LoggedPlayerResolverInterface::class),
            $this->sleepProvider,
        );
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForAValidNight(): void
    {
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn(null);

        $this->validator->validate($this->player, new LogSleepDataInput(
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
            3,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesWithoutAQualityScore(): void
    {
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn(null);

        $this->validator->validate($this->player, new LogSleepDataInput(
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsWakeBeforeOrEqualToBed(): void
    {
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn(null);

        try {
            $this->validator->validate($this->player, new LogSleepDataInput(
                new \DateTimeImmutable('2026-05-07T07:00:00Z'),
                new \DateTimeImmutable('2026-05-07T07:00:00Z'),
                3,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogSleepValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('wakeAt', $e->violations);
        }
    }

    public function testItRejectsAQualityOutOfRange(): void
    {
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn(null);

        try {
            $this->validator->validate($this->player, new LogSleepDataInput(
                new \DateTimeImmutable('2026-05-06T23:00:00Z'),
                new \DateTimeImmutable('2026-05-07T07:00:00Z'),
                6,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogSleepValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('quality', $e->violations);
        }
    }

    public function testItRejectsADuplicateNight(): void
    {
        $existing = new SleepDailyEntryDataModel(
            $this->player,
            new \DateTimeImmutable('2026-05-07'),
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
        );
        $existing->id = 'sleep-existing';
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn($existing);

        try {
            $this->validator->validate($this->player, new LogSleepDataInput(
                new \DateTimeImmutable('2026-05-06T22:30:00Z'),
                new \DateTimeImmutable('2026-05-07T06:30:00Z'),
                4,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogSleepValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('date', $e->violations);
        }
    }

    public function testItAccumulatesViolations(): void
    {
        $existing = new SleepDailyEntryDataModel(
            $this->player,
            new \DateTimeImmutable('2026-05-07'),
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
        );
        $existing->id = 'sleep-existing';
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn($existing);

        try {
            $this->validator->validate($this->player, new LogSleepDataInput(
                new \DateTimeImmutable('2026-05-07T08:00:00Z'),
                new \DateTimeImmutable('2026-05-07T07:00:00Z'),
                0,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('wakeAt', $e->violations);
            self::assertArrayHasKey('quality', $e->violations);
            self::assertArrayHasKey('date', $e->violations);
        }
    }

    private static function buildPlayer(string $id): PlayerDataModel
    {
        $user = new UserDataModel($id.'@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';
        $player = new PlayerDataModel($user, 'Tester '.$id);
        $player->id = $id;

        return $player;
    }
}
