<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Leveling;

use App\Domain\DTO\DataInput\Player\Leveling\EarnedExperience\ListEarnedExperienceDataInput;
use App\UseCase\Player\Leveling\EarnedExperience\ListEarnedExperienceUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class EarnedExperiencePlayerController
{
    #[Route(path: '/api/player/leveling/journal', name: 'player_leveling_journal', methods: ['GET'])]
    public function journal(Request $request, ListEarnedExperienceUseCase $useCase): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $perPage = (int) $request->query->get('perPage', (string) ListEarnedExperienceDataInput::DEFAULT_PER_PAGE);

        return new JsonResponse($useCase->execute(new ListEarnedExperienceDataInput($page, $perPage)));
    }
}
