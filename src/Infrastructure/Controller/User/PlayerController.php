<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\UseCase\User\RegisterPlayerUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PlayerController
{
    #[Route(path: '/api/player/registration', name: 'player_registration', methods: ['POST'])]
    public function register(Request $request, RegisterPlayerUseCase $useCase): JsonResponse
    {
        /** @var array{email?: string, plainPassword?: string, displayName?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new RegisterPlayerDataInput(
            (string) ($payload['email'] ?? ''),
            (string) ($payload['plainPassword'] ?? ''),
            (string) ($payload['displayName'] ?? ''),
        ));

        return new JsonResponse($output, 201);
    }
}
