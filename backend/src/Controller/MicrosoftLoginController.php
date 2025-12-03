<?php

namespace App\Controller;

use App\Service\MicrosoftUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TheNetworg\OAuth2\Client\Provider\Azure;

class MicrosoftLoginController extends AbstractController
{
    private Azure $provider;
    private MicrosoftUserService $userService;

    public function __construct(MicrosoftUserService $userService)
    {
        $this->userService = $userService;

        $this->provider = new Azure([
            'clientId' => $_ENV['AZURE_CLIENT_ID'],
            'clientSecret' => $_ENV['AZURE_CLIENT_SECRET'],
            'tenant' => $_ENV['AZURE_TENANT_ID'] . '/v2.0', // Force Microsoft Graph V2
            'redirectUri' => $_ENV['AZURE_REDIRECT_URI'],
            'resource' => 'https://graph.microsoft.com',
            'debug' => false,
        ]);
    }

    #[Route('/microsoft', name: 'microsoft', methods: ['GET'])]
    public function login(): Response
    {
        try {
            $authUrl = $this->provider->getAuthorizationUrl([
                'scope' => [
                    'openid',
                    'profile',
                    'offline_access',
                    'https://graph.microsoft.com/User.Read',
                    'email'
                ],
                'disableState' => true
            ]);

            return $this->redirect($authUrl);
        } catch (\Throwable $e) {
            return new Response('Login-Fehler: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/auth', name: 'auth_alias', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        try {
            if (!$request->get('code')) {
                return new Response('Kein Code erhalten.', 400);
            }

            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
                'disableState' => true
            ]);

            // ✔ User von Microsoft Graph holen
            $graphUser = $this->provider->get("https://graph.microsoft.com/v1.0/me", $token);

            $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'];
            $vorname = $graphUser['givenName'] ?? '';
            $nachname = $graphUser['surname'] ?? '';

            if (!$email) {
                return new Response("Die Microsoft API hat keine E-Mail zurückgegeben.", 400);
            }

            // ✔ User speichern / Rolle bestimmen / Redirect zurückgeben
            $redirectUrl = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);
            return $this->redirect($redirectUrl);

        } catch (\Throwable $e) {
            return new Response("Allgemeiner Fehler: " . $e->getMessage(), 500);
        }
    }
}
