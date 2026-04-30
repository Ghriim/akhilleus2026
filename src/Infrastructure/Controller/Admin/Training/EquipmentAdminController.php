<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\Training;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\DeleteEquipmentDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\GetEquipmentDetailsDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\ListEquipmentsDataInput;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\UpdateEquipmentDataInput;
use App\UseCase\Admin\Training\Equipment\CreateEquipmentUseCase;
use App\UseCase\Admin\Training\Equipment\DeleteEquipmentUseCase;
use App\UseCase\Admin\Training\Equipment\GetEquipmentDetailsUseCase;
use App\UseCase\Admin\Training\Equipment\ListEquipmentsUseCase;
use App\UseCase\Admin\Training\Equipment\UpdateEquipmentUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class EquipmentAdminController
{
    #[Route(path: '/api/admin/equipments', name: 'admin_equipment_list', methods: ['GET'])]
    public function list(Request $request, ListEquipmentsUseCase $useCase): JsonResponse
    {
        $sort = (string) $request->query->get('sort', 'label');
        $direction = (string) $request->query->get('direction', 'ASC');

        return new JsonResponse($useCase->execute(new ListEquipmentsDataInput($sort, $direction)));
    }

    #[Route(path: '/api/admin/equipments/{id}', name: 'admin_equipment_get', methods: ['GET'])]
    public function get(string $id, GetEquipmentDetailsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetEquipmentDetailsDataInput($id)));
    }

    #[Route(path: '/api/admin/equipments', name: 'admin_equipment_create', methods: ['POST'])]
    public function create(Request $request, CreateEquipmentUseCase $useCase): JsonResponse
    {
        /** @var array{label?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new CreateEquipmentDataInput((string) ($payload['label'] ?? '')));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/admin/equipments/{id}', name: 'admin_equipment_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateEquipmentUseCase $useCase): JsonResponse
    {
        /** @var array{label?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new UpdateEquipmentDataInput($id, (string) ($payload['label'] ?? '')));

        return new JsonResponse($output);
    }

    #[Route(path: '/api/admin/equipments/{id}', name: 'admin_equipment_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteEquipmentUseCase $useCase): JsonResponse
    {
        $useCase->execute(new DeleteEquipmentDataInput($id));

        return new JsonResponse(null, 204);
    }
}
