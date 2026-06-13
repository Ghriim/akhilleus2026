<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\LogWeightDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class LogWeightValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'LOG_WEIGHT_VALIDATION_FAILED';

    public function __construct(
        LoggedPlayerResolverInterface $loggedPlayerResolver,
        private WeightEntryProviderGateway $weightProvider,
    ) {
        parent::__construct($loggedPlayerResolver);
    }

    public function validate(PlayerDataModel $player, LogWeightDataInput $input): void
    {
        $violations = [];

        if (0 >= $input->valueGrams) {
            $violations['valueGrams'][] = 'Weight must be a positive number of grams.';
        }

        $date = $input->loggedAt->setTime(0, 0, 0);
        if (null !== $this->weightProvider->findOneByPlayerAndDate($player, $date)) {
            $violations['date'][] = 'A weight entry already exists for this day.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Weight data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
