<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdateSleepDataInput;
use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Sleep\UpdateSleepValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateSleepValidatorTest extends TestCase
{
    private SleepDailyEntryProviderGateway&MockObject $sleepProvider;
    private UpdateSleepValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->sleepProvider = $this->createMock(SleepDailyEntryProviderGateway::class);
        $this->validator = new UpdateSleepValidator(
            $this->createMock(LoggedPlayerResolverInterface::class),
            $this->sleepProvider,
        );
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForAValidUpdate(): void
    {
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn(null);

        $this->validator->validate($this->player, new UpdateSleepDataInput(
            'sleep-1',
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
            3,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesWhenTheOnlyEntryOnThatNightIsTheOneBeingUpdated(): void
    {
        $self = new SleepDailyEntryDataModel(
            $this->player,
            new \DateTimeImmutable('2026-05-07'),
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
        );
        $self->id = 'sleep-1';
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn($self);

        $this->validator->validate($this->player, new UpdateSleepDataInput(
            'sleep-1',
            new \DateTimeImmutable('2026-05-06T22:00:00Z'),
            new \DateTimeImmutable('2026-05-07T06:00:00Z'),
            4,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsWakeBeforeOrEqualToBed(): void
    {
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn(null);

        try {
            $this->validator->validate($this->player, new UpdateSleepDataInput(
                'sleep-1',
                new \DateTimeImmutable('2026-05-07T07:00:00Z'),
                new \DateTimeImmutable('2026-05-07T06:00:00Z'),
                3,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateSleepValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('wakeAt', $e->violations);
        }
    }

    public function testItRejectsAQualityOutOfRange(): void
    {
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn(null);

        try {
            $this->validator->validate($this->player, new UpdateSleepDataInput(
                'sleep-1',
                new \DateTimeImmutable('2026-05-06T23:00:00Z'),
                new \DateTimeImmutable('2026-05-07T07:00:00Z'),
                0,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateSleepValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('quality', $e->violations);
        }
    }

    public function testItRejectsMovingToANightOwnedByAnotherEntry(): void
    {
        $other = new SleepDailyEntryDataModel(
            $this->player,
            new \DateTimeImmutable('2026-05-07'),
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
        );
        $other->id = 'sleep-other';
        $this->sleepProvider->method('findOneByPlayerAndDate')->willReturn($other);

        try {
            $this->validator->validate($this->player, new UpdateSleepDataInput(
                'sleep-1',
                new \DateTimeImmutable('2026-05-06T22:00:00Z'),
                new \DateTimeImmutable('2026-05-07T06:00:00Z'),
                4,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateSleepValidator::ERROR_CODE, $e->errorCode);
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
