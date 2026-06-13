<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdateSleepDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdateSleepValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_SLEEP_VALIDATION_FAILED';

    public function __construct(
        LoggedPlayerResolverInterface $loggedPlayerResolver,
        private SleepDailyEntryProviderGateway $sleepProvider,
    ) {
        parent::__construct($loggedPlayerResolver);
    }

    public function validate(PlayerDataModel $player, UpdateSleepDataInput $input): void
    {
        $violations = [];

        if ($input->wakeAt <= $input->bedAt) {
            $violations['wakeAt'][] = 'Wake time must be after bed time.';
        }

        if (null !== $input->quality && (1 > $input->quality || 5 < $input->quality)) {
            $violations['quality'][] = 'Sleep quality must be between 1 and 5.';
        }

        // Moving the night to a date already taken by another of the player's entries collides
        // with the (player, date) unique constraint — reject gracefully (the entry keeping its
        // own date is fine, hence the id exclusion).
        $date = $input->wakeAt->setTime(0, 0, 0);
        $existing = $this->sleepProvider->findOneByPlayerAndDate($player, $date);
        if (null !== $existing && $existing->id !== $input->id) {
            $violations['date'][] = 'A sleep entry already exists for this night.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Sleep data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
