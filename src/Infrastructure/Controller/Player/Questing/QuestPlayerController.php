<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Questing;

use App\Domain\DTO\DataInput\Player\Questing\ClaimQuestRewardDataInput;
use App\Domain\DTO\DataInput\Player\Questing\ListQuestsDataInput;
use App\UseCase\Player\Questing\ClaimQuestRewardUseCase;
use App\UseCase\Player\Questing\ListDailyQuestsUseCase;
use App\UseCase\Player\Questing\ListMonthlyQuestsUseCase;
use App\UseCase\Player\Questing\ListUniqueQuestsUseCase;
use App\UseCase\Player\Questing\ListWeeklyQuestsUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class QuestPlayerController
{
    #[Route(path: '/api/player/quests/daily', name: 'player_quests_list_daily', methods: ['GET'])]
    public function listDaily(ListDailyQuestsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListQuestsDataInput()));
    }

    #[Route(path: '/api/player/quests/weekly', name: 'player_quests_list_weekly', methods: ['GET'])]
    public function listWeekly(ListWeeklyQuestsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListQuestsDataInput()));
    }

    #[Route(path: '/api/player/quests/monthly', name: 'player_quests_list_monthly', methods: ['GET'])]
    public function listMonthly(ListMonthlyQuestsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListQuestsDataInput()));
    }

    #[Route(path: '/api/player/quests/unique', name: 'player_quests_list_unique', methods: ['GET'])]
    public function listUnique(ListUniqueQuestsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListQuestsDataInput()));
    }

    #[Route(path: '/api/player/quests/{progressionId}/claim', name: 'player_quests_claim', methods: ['POST'], requirements: ['progressionId' => '[0-9A-HJKMNP-TV-Z]{26}'])]
    public function claim(string $progressionId, ClaimQuestRewardUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ClaimQuestRewardDataInput($progressionId)));
    }
}
