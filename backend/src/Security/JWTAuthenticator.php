<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JWTAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        // Cookie muss gesetzt sein
        return $request->cookies->has('auth_token');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->cookies->get('auth_token');

        if (!$token) {
            throw new AuthenticationException("Kein Token vorhanden");
        }

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            $email = $decoded->email ?? null;
            $role = $decoded->role ?? 'Unbekannt';

            if (!$email) {
                throw new AuthenticationException("Ungültiges Token: Keine Email enthalten");
            }

            return new SelfValidatingPassport(
                new UserBadge($email, function () use ($email, $role) {
                    return new JWTUser($email, $role);
                })
            );

        } catch (\Throwable $e) {
            throw new AuthenticationException("Ungültiges Token: " . $e->getMessage());
        }
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response("Unauthorized: " . $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        // Einfach weiterlaufen lassen → Controller antwortet
        return null;
    }
}
