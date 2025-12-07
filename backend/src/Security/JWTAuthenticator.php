<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JWTAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->cookies->has('auth_token'); // Cookie-Check
    }

    public function authenticate(Request $request)
    {
        $token = $request->cookies->get('auth_token');

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $email = $decoded->email;
            $role  = $decoded->role;

            return new SelfValidatingPassport(
                new UserBadge($email, function() use ($email, $role) {
                    return new JWTUser($email, $role);
                })
            );
        } catch (\Exception $e) {
            throw new \Symfony\Component\Security\Core\Exception\AuthenticationException('Ungültiges Token');
        }
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?Response
    {
        return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return null; // API geht weiter
    }
}
