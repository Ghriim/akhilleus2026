<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\UpdateWeightDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdateWeightValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_WEIGHT_VALIDATION_FAILED';

    public function __construct(
        LoggedPlayerResolverInterface $loggedPlayerResolver,
        private WeightEntryProviderGateway $weightProvider,
    ) {
        parent::__construct($loggedPlayerResolver);
    }

    public function validate(PlayerDataModel $player, UpdateWeightDataInput $input): void
    {
        $violations = [];

        if (0 >= $input->valueGrams) {
            $violations['valueGrams'][] = 'Weight must be a positive number of grams.';
        }

        // Moving the entry to a day already taken by another of the player's entries collides
        // with the (player, date) unique constraint — reject gracefully (keeping its own day is fine).
        $date = $input->loggedAt->setTime(0, 0, 0);
        $existing = $this->weightProvider->findOneByPlayerAndDate($player, $date);
        if (null !== $existing && $existing->id !== $input->id) {
            $violations['date'][] = 'A weight entry already exists for this day.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Weight data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
