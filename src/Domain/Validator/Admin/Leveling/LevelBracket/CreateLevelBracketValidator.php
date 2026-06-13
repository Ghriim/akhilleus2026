<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\CreateLevelBracketDataInput;
use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Service\Leveling\LevelCurveEvaluator;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class CreateLevelBracketValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'LEVEL_BRACKET_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private LevelBracketProviderGateway $levelBracketProvider,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(CreateLevelBracketDataInput $input): void
    {
        $violations = LevelCurveEvaluator::collectFieldViolations(
            $input->fromLevel,
            $input->toLevel,
            $input->exponentK,
        );

        if ([] === $violations) {
            $candidate = new LevelBracketDataModel(
                $input->fromLevel,
                $input->toLevel,
                $input->coefficientA,
                $input->exponentK,
                $input->offsetB,
            );
            $resultingCurve = LevelCurveEvaluator::sortByFromLevel(
                [...$this->levelBracketProvider->findAllOrderedAsc(), $candidate],
            );

            foreach (LevelCurveEvaluator::collectCurveViolations($resultingCurve) as $message) {
                $violations['curve'][] = $message;
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Level bracket creation data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
