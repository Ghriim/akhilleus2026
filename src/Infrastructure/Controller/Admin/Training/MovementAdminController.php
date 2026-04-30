<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\Training;

use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\DeleteMovementDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\GetMovementDetailsDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\ListMovementsDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Movement\UpdateMovementDataInput;
use App\UseCase\Admin\Training\Movement\CreateMovementUseCase;
use App\UseCase\Admin\Training\Movement\DeleteMovementUseCase;
use App\UseCase\Admin\Training\Movement\GetMovementDetailsUseCase;
use App\UseCase\Admin\Training\Movement\ListMovementsUseCase;
use App\UseCase\Admin\Training\Movement\UpdateMovementUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MovementAdminController
{
    #[Route(path: '/api/admin/movements', name: 'admin_movement_list', methods: ['GET'])]
    public function list(ListMovementsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListMovementsDataInput()));
    }

    #[Route(path: '/api/admin/movements/{id}', name: 'admin_movement_get', methods: ['GET'])]
    public function get(string $id, GetMovementDetailsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetMovementDetailsDataInput($id)));
    }

    #[Route(path: '/api/admin/movements', name: 'admin_movement_create', methods: ['POST'])]
    public function create(Request $request, CreateMovementUseCase $useCase): JsonResponse
    {
        $payload = $this->decode($request);

        $output = $useCase->execute(new CreateMovementDataInput(
            (string) ($payload['label'] ?? ''),
            (string) ($payload['mainMuscleId'] ?? ''),
            $this->stringList($payload['secondaryMuscleIds'] ?? []),
            $this->stringList($payload['equipmentIds'] ?? []),
            (bool) ($payload['tracksRepetitions'] ?? false),
            (bool) ($payload['tracksWeight'] ?? false),
            (bool) ($payload['tracksDuration'] ?? false),
            (bool) ($payload['tracksDistance'] ?? false),
            (bool) ($payload['tracksInclinePercent'] ?? false),
            (bool) ($payload['tracksInclineMeters'] ?? false),
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/admin/movements/{id}', name: 'admin_movement_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateMovementUseCase $useCase): JsonResponse
    {
        $payload = $this->decode($request);

        $output = $useCase->execute(new UpdateMovementDataInput(
            $id,
            (string) ($payload['label'] ?? ''),
            (string) ($payload['mainMuscleId'] ?? ''),
            $this->stringList($payload['secondaryMuscleIds'] ?? []),
            $this->stringList($payload['equipmentIds'] ?? []),
            (bool) ($payload['tracksRepetitions'] ?? false),
            (bool) ($payload['tracksWeight'] ?? false),
            (bool) ($payload['tracksDuration'] ?? false),
            (bool) ($payload['tracksDistance'] ?? false),
            (bool) ($payload['tracksInclinePercent'] ?? false),
            (bool) ($payload['tracksInclineMeters'] ?? false),
        ));

        return new JsonResponse($output);
    }

    #[Route(path: '/api/admin/movements/{id}', name: 'admin_movement_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteMovementUseCase $useCase): JsonResponse
    {
        $useCase->execute(new DeleteMovementDataInput($id));

        return new JsonResponse(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Request $request): array
    {
        $decoded = json_decode($request->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $raw): array
    {
        if (false === is_array($raw)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $v) => (string) $v, $raw));
    }
}
