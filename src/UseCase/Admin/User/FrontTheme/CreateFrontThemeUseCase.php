<?php

declare(strict_types=1);

namespace App\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\CreateFrontThemeDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\DTO\DataOutput\Admin\User\FrontTheme\FrontThemeDataOutput;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\Domain\Storage\ImageStorageInterface;
use App\Domain\Validator\Admin\User\FrontTheme\CreateFrontThemeValidator;
use App\UseCase\AbstractLoggedAdminUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class CreateFrontThemeUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly CreateFrontThemeValidator $createFrontThemeValidator,
        private readonly FrontThemePersisterGateway $frontThemePersister,
        private readonly ImageStorageInterface $imageStorage,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param CreateFrontThemeDataInput $input
     */
    public function execute(DataInputInterface $input): FrontThemeDataOutput
    {
        $this->createFrontThemeValidator->validate($input);

        $theme = new FrontThemeDataModel($input->name);
        $theme->description = $input->description;
        if (null !== $input->imageSourcePath) {
            $theme->imageFilename = $this->imageStorage->store($input->imageSourcePath, $input->imageExtension ?? 'png');
        }

        $this->frontThemePersister->create($theme);

        $output = $this->mapper->map($theme, FrontThemeDataOutput::class);
        $output->imagePreviewUrl = $this->imageStorage->publicUrl($theme->imageFilename);

        return $output;
    }
}
