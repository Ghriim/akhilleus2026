<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SecurityController
{
    /**
     * Stub action — the JSON-login authenticator on the `security` firewall
     * intercepts this request and exchanges the credentials for a JWT.
     * The route only exists to anchor `/api/security/login` in the routing layer;
     * this method is never reached on a successful match.
     */
    #[Route(path: '/api/security/login', name: 'security_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse(['error' => 'Login endpoint should be handled by the security firewall.'], 500);
    }

    /**
     * Stateless JWTs mean the client just discards its token; this endpoint
     * exists as a logout signal for clients/audit logs and returns 204.
     * If true server-side invalidation becomes a requirement later, swap in
     * a JWT blocklist (e.g. Redis-backed) without changing the URL.
     */
    #[Route(path: '/api/security/logout', name: 'security_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }
}
