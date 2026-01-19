<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiJWTAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $authHeader = $request->headers->get('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('Kein Bearer Token');
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode(
                $token,
                new Key($_ENV['JWT_SECRET'], 'HS256')
            );
        } catch (\Throwable $e) {
            throw new AuthenticationException('Ungültiges Token');
        }

        if (!isset($decoded->email, $decoded->role)) {
            throw new AuthenticationException('Token unvollständig');
        }

        return new SelfValidatingPassport(
            new UserBadge($decoded->email, function () use ($decoded) {
                return new JWTUser($decoded->email, $decoded->role);
            })
        );
    }

    /** ✅ PFLICHT in Symfony 7 */
    public function onAuthenticationSuccess(
        Request $request,
        $token,
        string $firewallName
    ): ?Response {
        // nichts tun → Request darf weiter
        return null;
    }

    /** ✅ PFLICHT in Symfony 7 */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new Response(
            json_encode([
                'error' => 'Unauthorized',
                'message' => $exception->getMessage(),
            ]),
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json']
        );
    }
}
