<?php

declare(strict_types=1);

namespace App\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\UpdateFrontThemeDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\User\FrontTheme\FrontThemeDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Storage\ImageStorageInterface;
use App\Domain\Validator\Admin\User\FrontTheme\UpdateFrontThemeValidator;
use App\UseCase\AbstractLoggedAdminUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateFrontThemeUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly UpdateFrontThemeValidator $updateFrontThemeValidator,
        private readonly FrontThemeProviderGateway $frontThemeProvider,
        private readonly FrontThemePersisterGateway $frontThemePersister,
        private readonly ImageStorageInterface $imageStorage,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateFrontThemeDataInput $input
     */
    public function execute(DataInputInterface $input): FrontThemeDataOutput
    {
        $theme = $this->frontThemeProvider->findOneByIdForAdminAction($input->id);
        if (null === $theme) {
            throw new EntityNotFoundException(sprintf('Front theme "%s" not found.', $input->id));
        }

        $this->updateFrontThemeValidator->validate($input, $theme);

        $theme->name = $input->name;
        $theme->description = $input->description;
        if (null !== $input->imageSourcePath) {
            $previous = $theme->imageFilename;
            $theme->imageFilename = $this->imageStorage->store($input->imageSourcePath, $input->imageExtension ?? 'png');
            $this->imageStorage->remove($previous);
        }

        $this->frontThemePersister->update($theme);

        $output = $this->mapper->map($theme, FrontThemeDataOutput::class);
        $output->imagePreviewUrl = $this->imageStorage->publicUrl($theme->imageFilename);

        return $output;
    }
}
