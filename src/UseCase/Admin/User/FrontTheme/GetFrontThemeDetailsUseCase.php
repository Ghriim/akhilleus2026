<?php

declare(strict_types=1);

namespace App\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\GetFrontThemeDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\User\FrontTheme\FrontThemeDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Storage\ImageStorageInterface;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class GetFrontThemeDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private FrontThemeProviderGateway $frontThemeProvider,
        private ImageStorageInterface $imageStorage,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param GetFrontThemeDetailsDataInput $input
     */
    public function execute(DataInputInterface $input): FrontThemeDataOutput
    {
        $theme = $this->frontThemeProvider->findOneByIdForAdminAction($input->id);
        if (null === $theme) {
            throw new EntityNotFoundException(sprintf('Front theme "%s" not found.', $input->id));
        }

        $output = $this->mapper->map($theme, FrontThemeDataOutput::class);
        $output->imagePreviewUrl = $this->imageStorage->publicUrl($theme->imageFilename);

        return $output;
    }
}
