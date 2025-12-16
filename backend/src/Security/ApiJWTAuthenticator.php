<?php

namespace App\Security;

use App\Entity\Schueler;
use App\Repository\SchuelerRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiJWTAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private SchuelerRepository $schuelerRepository
    ) {}

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
            throw new AuthenticationException('Ungültiges JWT');
        }

        $email = $decoded->email ?? null;

        if (!$email) {
            throw new AuthenticationException('Token ohne Email');
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $email,
                fn ($email) => $this->schuelerRepository->findOneBy(['email' => $email])
            )
        );
    }
}
