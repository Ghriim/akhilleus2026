<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\LogWeightDataInput;
use App\Domain\DTO\DataModel\Tracking\Weight\WeightEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\LogWeightValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class LogWeightValidatorTest extends TestCase
{
    private WeightEntryProviderGateway&MockObject $weightProvider;
    private LogWeightValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->weightProvider = $this->createMock(WeightEntryProviderGateway::class);
        $this->validator = new LogWeightValidator(
            $this->createMock(LoggedPlayerResolverInterface::class),
            $this->weightProvider,
        );
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForAValidEntry(): void
    {
        $this->weightProvider->method('findOneByPlayerAndDate')->willReturn(null);

        $this->validator->validate($this->player, new LogWeightDataInput(new \DateTimeImmutable('2026-05-07T08:00:00Z'), 82000));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsANonPositiveWeight(): void
    {
        $this->weightProvider->method('findOneByPlayerAndDate')->willReturn(null);

        try {
            $this->validator->validate($this->player, new LogWeightDataInput(new \DateTimeImmutable('2026-05-07T08:00:00Z'), 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogWeightValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueGrams', $e->violations);
        }
    }

    public function testItRejectsADuplicateDay(): void
    {
        $existing = new WeightEntryDataModel($this->player, new \DateTimeImmutable('2026-05-07T07:00:00Z'), 81000);
        $existing->id = 'weight-existing';
        $this->weightProvider->method('findOneByPlayerAndDate')->willReturn($existing);

        try {
            $this->validator->validate($this->player, new LogWeightDataInput(new \DateTimeImmutable('2026-05-07T20:00:00Z'), 82000));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogWeightValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('date', $e->violations);
        }
    }

    public function testItAccumulatesViolations(): void
    {
        $existing = new WeightEntryDataModel($this->player, new \DateTimeImmutable('2026-05-07T07:00:00Z'), 81000);
        $existing->id = 'weight-existing';
        $this->weightProvider->method('findOneByPlayerAndDate')->willReturn($existing);

        try {
            $this->validator->validate($this->player, new LogWeightDataInput(new \DateTimeImmutable('2026-05-07T20:00:00Z'), -10));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('valueGrams', $e->violations);
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
