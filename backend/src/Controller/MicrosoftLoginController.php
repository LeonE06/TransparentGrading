<?php

namespace App\Controller;

use App\Service\MicrosoftUserService;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
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

        // Azure Credentials laden
        $clientId = $_ENV['AZURE_CLIENT_ID'];
        $clientSecret = $_ENV['AZURE_CLIENT_SECRET'];
        $tenant = $_ENV['AZURE_TENANT_ID'];
        $redirectUri = $_ENV['AZURE_REDIRECT_URI'];

        // Provider korrekt konfigurieren
        $this->provider = new Azure([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'tenant' => $tenant,
            'redirectUri' => $redirectUri,
        ]);
    }

    #[Route('/microsoft', name: 'microsoft', methods: ['GET'])]
    public function login(): Response
    {
        try {
            // KORREKTE SCOPE-LISTE
            $authUrl = $this->provider->getAuthorizationUrl([
                'scope' => [
                    'openid',
                    'profile',
                    'offline_access',
                    'https://graph.microsoft.com/User.Read'
                ],
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

            // Token holen
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            // Microsoft Graph User
            $graphUser = $this->provider->get("https://graph.microsoft.com/v1.0/me", $token);

            // Daten extrahieren
            $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'];
            $vorname = $graphUser['givenName'];
            $nachname = $graphUser['surname'];

            // Service verarbeitet User und gibt Redirect-URL zurück
            $redirectUrl = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

            return $this->redirect($redirectUrl);

        } catch (\Throwable $e) {
            return new Response("Allgemeiner Fehler: " . $e->getMessage(), 500);
        }
    }
}
