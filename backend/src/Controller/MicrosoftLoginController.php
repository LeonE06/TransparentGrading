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
        $clientId = $_SERVER['AZURE_CLIENT_ID'] ?? $_ENV['AZURE_CLIENT_ID'] ?? null;
        $clientSecret = $_SERVER['AZURE_CLIENT_SECRET'] ?? $_ENV['AZURE_CLIENT_SECRET'] ?? null;
        $tenant = $_SERVER['AZURE_TENANT_ID'] ?? $_ENV['AZURE_TENANT_ID'] ?? null;
        $redirectUri = $_SERVER['AZURE_REDIRECT_URI'] ?? $_ENV['AZURE_REDIRECT_URI'] ?? null;

        // Provider für Microsoft Graph konfigurieren
        $this->provider = new Azure([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'tenant' => $tenant,
            'redirectUri' => $redirectUri,
            'resource' => 'https://graph.microsoft.com/',
            'debug' => false,
        ]);
    }

    #[Route('/microsoft', name: 'microsoft', methods: ['GET'])]
    public function login(): Response
    {
        try {
            // Login-URL bauen
            $authUrl = $this->provider->getAuthorizationUrl([
                'scope' => [
                    'openid',
                    'profile',
                    'email',
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
            // Code prüfen
            if (!$request->get('code')) {
                return new Response('Kein Code erhalten.', 400);
            }

            // Access Token holen
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            // Microsoft Graph → /me
            $graphUser = $this->provider->get("https://graph.microsoft.com/v1.0/me", $token);

            // Userdaten extrahieren
            $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'] ?? null;
            $vorname = $graphUser['givenName'] ?? 'Unbekannt';
            $nachname = $graphUser['surname'] ?? 'Unbekannt';

            if (!$email) {
                return new Response('Keine gültige E-Mail-Adresse erhalten.', 400);
            }

            // Benutzer erstellen oder abrufen
            $redirectUrl = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

            // Weiterleitung ins Frontend
            return $this->redirect($redirectUrl);

        } catch (IdentityProviderException $e) {
            return new Response("Azure Error: " . $e->getMessage(), 500);

        } catch (\Throwable $e) {
            return new Response("Allgemeiner Fehler: " . $e->getMessage(), 500);
        }
    }
}
