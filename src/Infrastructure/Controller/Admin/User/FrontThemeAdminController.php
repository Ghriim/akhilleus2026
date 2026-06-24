<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\User;

use App\Domain\DTO\DataInput\Admin\User\FrontTheme\CreateFrontThemeDataInput;
use App\Domain\DTO\DataInput\Admin\User\FrontTheme\DeleteFrontThemeDataInput;
use App\Domain\DTO\DataInput\Admin\User\FrontTheme\GetFrontThemeDetailsDataInput;
use App\Domain\DTO\DataInput\Admin\User\FrontTheme\ListFrontThemesDataInput;
use App\Domain\DTO\DataInput\Admin\User\FrontTheme\UpdateFrontThemeDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Admin\User\FrontTheme\CreateFrontThemeUseCase;
use App\UseCase\Admin\User\FrontTheme\DeleteFrontThemeUseCase;
use App\UseCase\Admin\User\FrontTheme\GetFrontThemeDetailsUseCase;
use App\UseCase\Admin\User\FrontTheme\ListFrontThemesUseCase;
use App\UseCase\Admin\User\FrontTheme\UpdateFrontThemeUseCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Create/Update use POST + multipart/form-data (PHP only populates $_FILES on POST, so a multipart
 * PUT would not expose the uploaded file — hence POST for the update too).
 */
final readonly class FrontThemeAdminController
{
    private const string IMAGE_INVALID = 'FRONT_THEME_IMAGE_INVALID';
    private const int MAX_BYTES = 2 * 1024 * 1024;

    /** @var array<string, string> mime => extension (no dot) */
    private const array ALLOWED = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    #[Route(path: '/api/admin/front-themes', name: 'admin_front_theme_list', methods: ['GET'])]
    public function list(ListFrontThemesUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListFrontThemesDataInput()));
    }

    #[Route(path: '/api/admin/front-themes/{id}', name: 'admin_front_theme_get', methods: ['GET'])]
    public function get(string $id, GetFrontThemeDetailsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetFrontThemeDetailsDataInput($id)));
    }

    #[Route(path: '/api/admin/front-themes', name: 'admin_front_theme_create', methods: ['POST'])]
    public function create(Request $request, CreateFrontThemeUseCase $useCase): JsonResponse
    {
        [$imagePath, $imageExt] = $this->resolveImage($request);

        $output = $useCase->execute(new CreateFrontThemeDataInput(
            (string) $request->request->get('name', ''),
            self::nullableString($request->request->get('description')),
            $imagePath,
            $imageExt,
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/admin/front-themes/{id}', name: 'admin_front_theme_update', methods: ['POST'])]
    public function update(string $id, Request $request, UpdateFrontThemeUseCase $useCase): JsonResponse
    {
        [$imagePath, $imageExt] = $this->resolveImage($request);

        $output = $useCase->execute(new UpdateFrontThemeDataInput(
            $id,
            (string) $request->request->get('name', ''),
            self::nullableString($request->request->get('description')),
            $imagePath,
            $imageExt,
        ));

        return new JsonResponse($output);
    }

    #[Route(path: '/api/admin/front-themes/{id}', name: 'admin_front_theme_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteFrontThemeUseCase $useCase): JsonResponse
    {
        $useCase->execute(new DeleteFrontThemeDataInput($id));

        return new JsonResponse(null, 204);
    }

    /**
     * Validates the optional uploaded "image" (png/jpeg, ≤ 2 MB) and returns [path, extension],
     * or [null, null] when no file was sent.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveImage(Request $request): array
    {
        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile) {
            return [null, null];
        }

        if (false === $file->isValid()) {
            throw new ValidationException('Theme image is invalid.', ['image' => ['Upload failed.']], self::IMAGE_INVALID);
        }

        if (self::MAX_BYTES < $file->getSize()) {
            throw new ValidationException('Theme image is invalid.', ['image' => ['Image must be 2 MB or smaller.']], self::IMAGE_INVALID);
        }

        $mime = (string) $file->getMimeType();
        if (false === isset(self::ALLOWED[$mime])) {
            throw new ValidationException('Theme image is invalid.', ['image' => ['Image must be a PNG or JPEG.']], self::IMAGE_INVALID);
        }

        return [$file->getPathname(), self::ALLOWED[$mime]];
    }

    private static function nullableString(mixed $value): ?string
    {
        return null === $value || '' === $value ? null : (string) $value;
    }
}
