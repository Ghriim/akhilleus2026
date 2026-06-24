<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\UpdateFrontThemeDataInput;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class UpdateFrontThemeValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'UPDATE_FRONT_THEME_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private FrontThemeProviderGateway $frontThemeProviderGateway,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(UpdateFrontThemeDataInput $input, FrontThemeDataModel $current): void
    {
        $violations = [];

        if ('' === trim($input->name)) {
            $violations['name'][] = 'Name must not be empty.';
        } else {
            $existing = $this->frontThemeProviderGateway->findOneByNameForUniqueness($input->name);
            if (null !== $existing && $existing->id !== $current->id) {
                $violations['name'][] = 'Another theme already uses this name.';
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Front theme update data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
