<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Training;

use App\Domain\DTO\DataInput\Player\Training\Movement\ListMovementsForPlayerDataInput;
use App\UseCase\Player\Training\Movement\ListMovementsForPlayerUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MovementPlayerController
{
    #[Route(path: '/api/player/movements', name: 'player_movement_list', methods: ['GET'])]
    public function list(ListMovementsForPlayerUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListMovementsForPlayerDataInput()));
    }
}
