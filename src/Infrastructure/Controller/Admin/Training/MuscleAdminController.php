<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\Training;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\DeleteMuscleDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\GetMuscleDetailsDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\ListMusclesDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\UpdateMuscleDataInput;
use App\UseCase\Admin\Training\Muscle\CreateMuscleUseCase;
use App\UseCase\Admin\Training\Muscle\DeleteMuscleUseCase;
use App\UseCase\Admin\Training\Muscle\GetMuscleDetailsUseCase;
use App\UseCase\Admin\Training\Muscle\ListMusclesUseCase;
use App\UseCase\Admin\Training\Muscle\UpdateMuscleUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MuscleAdminController
{
    #[Route(path: '/api/admin/muscles', name: 'admin_muscle_list', methods: ['GET'])]
    public function list(Request $request, ListMusclesUseCase $useCase): JsonResponse
    {
        $sort = (string) $request->query->get('sort', 'label');
        $direction = (string) $request->query->get('direction', 'ASC');

        return new JsonResponse($useCase->execute(new ListMusclesDataInput($sort, $direction)));
    }

    #[Route(path: '/api/admin/muscles/{id}', name: 'admin_muscle_get', methods: ['GET'])]
    public function get(string $id, GetMuscleDetailsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetMuscleDetailsDataInput($id)));
    }

    #[Route(path: '/api/admin/muscles', name: 'admin_muscle_create', methods: ['POST'])]
    public function create(Request $request, CreateMuscleUseCase $useCase): JsonResponse
    {
        /** @var array{label?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new CreateMuscleDataInput((string) ($payload['label'] ?? '')));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/admin/muscles/{id}', name: 'admin_muscle_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateMuscleUseCase $useCase): JsonResponse
    {
        /** @var array{label?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new UpdateMuscleDataInput($id, (string) ($payload['label'] ?? '')));

        return new JsonResponse($output);
    }

    #[Route(path: '/api/admin/muscles/{id}', name: 'admin_muscle_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteMuscleUseCase $useCase): JsonResponse
    {
        $useCase->execute(new DeleteMuscleDataInput($id));

        return new JsonResponse(null, 204);
    }
}
