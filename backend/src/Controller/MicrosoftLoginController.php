<?php

namespace App\Controller;

use App\Service\MicrosoftUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TheNetworg\OAuth2\Client\Provider\Azure;
use Firebase\JWT\JWT;

class MicrosoftLoginController extends AbstractController
{
    private Azure $provider;
    private MicrosoftUserService $mUserService;

    public function __construct(MicrosoftUserService $mUserService)
    {
        $this->mUserService = $mUserService;

        $this->provider = new Azure([
            'clientId' => $_ENV['AZURE_CLIENT_ID'],
            'clientSecret' => $_ENV['AZURE_CLIENT_SECRET'],
            'tenant' => $_ENV['AZURE_TENANT_ID'] . '/v2.0',
            'redirectUri' => $_ENV['AZURE_REDIRECT_URI'],
            'resource' => 'https://graph.microsoft.com',
            'debug' => false
        ]);
    }

    #[Route('/microsoft', name: 'app_microsoft_login')]
    public function login(): Response
    {
        $authUrl = $this->provider->getAuthorizationUrl([
            'scope' => [
                'openid',
                'profile',
                'offline_access',
                'email',
                'https://graph.microsoft.com/User.Read'
            ],
            'disableState' => true
        ]);

        return $this->redirect($authUrl);
    }

    #[Route('/auth', name: 'app_microsoft_callback')]
    public function callback(Request $request): Response
    {
        if (!$request->get('code')) {
            return new Response("Kein Code erhalten", 400);
        }

        try {
            $tokenMicrosoft = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
                'disableState' => true
            ]);

            $graphUser = $this->provider->get("https://graph.microsoft.com/v1.0/me", $tokenMicrosoft);

            $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'];
            $vorname = $graphUser['givenName'] ?? '';
            $nachname = $graphUser['surname'] ?? '';

            $role = $this->mUserService->handleMicrosoftUser($vorname, $nachname, $email);

            $payload = [
                'email' => $email,
                'role' => $role,
                'exp' => time() + 3600
            ];

            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            $frontendUrl = $_ENV['FRONTEND_URL'];

            return $this->redirect("{$frontendUrl}/auth/callback?token={$jwt}");

        } catch (\Throwable $e) {
            return new Response("Fehler: " . $e->getMessage(), 500);
        }
    }
}
