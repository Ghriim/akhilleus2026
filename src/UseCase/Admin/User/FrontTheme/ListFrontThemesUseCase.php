<?php

declare(strict_types=1);

namespace App\UseCase\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\User\FrontTheme\FrontThemeListItemDataOutput;
use App\Domain\Gateway\Provider\User\FrontThemeProviderGateway;
use App\Domain\Storage\ImageStorageInterface;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ListFrontThemesUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private FrontThemeProviderGateway $frontThemeProvider,
        private ImageStorageInterface $imageStorage,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param \App\Domain\DTO\DataInput\Admin\User\FrontTheme\ListFrontThemesDataInput $input
     *
     * @return list<FrontThemeListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $outputs = [];
        foreach ($this->frontThemeProvider->findAllForAdminList() as $theme) {
            $output = $this->mapper->map($theme, FrontThemeListItemDataOutput::class);
            $output->imagePreviewUrl = $this->imageStorage->publicUrl($theme->imageFilename);
            $outputs[] = $output;
        }

        return $outputs;
    }
}
