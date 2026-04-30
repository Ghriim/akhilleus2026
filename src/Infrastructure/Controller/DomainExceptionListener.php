<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Exception\ValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

#[AsEventListener(event: ExceptionEvent::class)]
final readonly class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof ValidationException => new JsonResponse([
                'message' => $exception->getMessage(),
                'errorCode' => $exception->errorCode,
                'violations' => $exception->violations,
            ], 422),
            $exception instanceof EntityNotFoundException => new JsonResponse([
                'message' => $exception->getMessage(),
            ], 404),
            $exception instanceof UnauthorizedException => new JsonResponse([
                'message' => $exception->getMessage(),
            ], 401),
            default => null,
        };

        if (null !== $response) {
            $event->setResponse($response);
        }
    }
}
