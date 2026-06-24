<?php

declare(strict_types=1);

namespace App\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\DeleteFrontThemeDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Storage\ImageStorageInterface;
use App\UseCase\AbstractLoggedAdminUseCase;

final class DeleteFrontThemeUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly FrontThemeProviderGateway $frontThemeProvider,
        private readonly FrontThemePersisterGateway $frontThemePersister,
        private readonly ImageStorageInterface $imageStorage,
    ) {
    }

    /**
     * @param DeleteFrontThemeDataInput $input
     */
    public function execute(DataInputInterface $input): null
    {
        $theme = $this->frontThemeProvider->findOneByIdForAdminAction($input->id);
        if (null === $theme) {
            throw new EntityNotFoundException(sprintf('Front theme "%s" not found.', $input->id));
        }

        $this->imageStorage->remove($theme->imageFilename);
        $this->frontThemePersister->delete($theme);

        return null;
    }
}
