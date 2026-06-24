<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\CreateFrontThemeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class CreateFrontThemeValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'CREATE_FRONT_THEME_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private FrontThemeProviderGateway $frontThemeProviderGateway,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(CreateFrontThemeDataInput $input): void
    {
        $violations = [];

        if ('' === trim($input->name)) {
            $violations['name'][] = 'Name must not be empty.';
        } elseif (null !== $this->frontThemeProviderGateway->findOneByNameForUniqueness($input->name)) {
            $violations['name'][] = 'Another theme already uses this name.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Front theme creation data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
