<?php

namespace App\Service;

use App\Entity\MicrosoftUser;
use App\Repository\MicrosoftUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MicrosoftUserService
{
    public function __construct(
        private HttpClientInterface $client,
        private EntityManagerInterface $em,
        private MicrosoftUserRepository $repo,
        private string $clientId = "DEINE_AZURE_CLIENT_ID",
        private string $clientSecret = "DEIN_CLIENT_SECRET",
        private string $tenantId = "common",
        private string $redirectUri = "https://transparentgrading.onrender.com/auth"
    ) {}

    public function getAuthUrl(): string
    {
        return "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/authorize?" .
            "client_id={$this->clientId}&response_type=code&redirect_uri={$this->redirectUri}" .
            "&response_mode=query&scope=openid%20profile%20email%20User.Read";
    }

    public function handleMicrosoftLogin(string $code): MicrosoftUser
    {
        $tokenData = $this->fetchAccessToken($code);
        $userData = $this->fetchUserData($tokenData['access_token']);

        $email = $userData['mail'] ?? $userData['userPrincipalName'];
        $role = $this->determineUserRole($email);

        $user = $this->repo->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new MicrosoftUser();
            $user->setVorname($userData['givenName']);
            $user->setNachname($userData['surname']);
            $user->setEmail($email);
            $user->setRole($role);

            $this->em->persist($user);
        } else {
            $user->setRole($role); // falls Lehrer → Schüler → Lehrer Änderung
        }

        $this->em->flush();
        return $user;
    }

    private function fetchAccessToken(string $code): array
    {
        $response = $this->client->request('POST', "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
            'body' => [
                'client_id' => $this->clientId,
                'scope' => 'User.Read',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code',
                'client_secret' => $this->clientSecret
            ]
        ]);

        return $response->toArray();
    }

    private function fetchUserData(string $accessToken): array
    {
        $response = $this->client->request('GET', "https://graph.microsoft.com/v1.0/me", [
            'headers' => [
                'Authorization' => "Bearer $accessToken"
            ]
        ]);

        return $response->toArray();
    }

    public function determineUserRole(string $email): string
    {
        $localPart = explode('@', $email)[0];

        if (ctype_digit($localPart)) {
            return "Schueler";
        }

        return "Lehrer";
    }

    public function generateJwtToken(MicrosoftUser $user): string
    {
        $payload = [
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'exp' => time() + 86400 // 24 Std
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }
}
