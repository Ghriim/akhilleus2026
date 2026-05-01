<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Training;

use App\Domain\DTO\DataInput\Player\Training\PersonalBest\ListPersonalBestsDataInput;
use App\UseCase\Player\Training\PersonalBest\ListPersonalBestsUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PersonalBestPlayerController
{
    #[Route(path: '/api/player/personal-bests', name: 'player_personal_best_list', methods: ['GET'])]
    public function list(ListPersonalBestsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListPersonalBestsDataInput()));
    }
}
