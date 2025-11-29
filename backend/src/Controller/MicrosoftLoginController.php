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

        // 🔒 Sichere Environment-Variablen laden (funktioniert in Docker und lokal)
        $clientId = $_SERVER['AZURE_CLIENT_ID'] ?? $_ENV['AZURE_CLIENT_ID'] ?? null;
        $clientSecret = $_SERVER['AZURE_CLIENT_SECRET'] ?? $_ENV['AZURE_CLIENT_SECRET'] ?? null;
        $tenant = $_SERVER['AZURE_TENANT_ID'] ?? $_ENV['AZURE_TENANT_ID'] ?? null;
        $redirectUri = $_SERVER['AZURE_REDIRECT_URI'] ?? $_ENV['AZURE_REDIRECT_URI'] ?? null;

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
            // 🔗 Microsoft-Login starten
            $authUrl = $this->provider->getAuthorizationUrl([
                'scope' => ['openid', 'profile', 'email', 'User.Read'],
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
            // 🔑 Access Token holen
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            // 👤 Benutzerdaten abrufen
            $graphUser = $this->provider->get("https://graph.microsoft.com/v1.0/me", $token);
            $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'] ?? null;
            $vorname = $graphUser['givenName'] ?? 'Unbekannt';
            $nachname = $graphUser['surname'] ?? 'Unbekannt';

            if (!$email) {
                return new Response('Keine gültige E-Mail-Adresse erhalten.', 400);
            }

            // 🧠 Benutzer anlegen oder abrufen
            $redirectUrl = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

            // 🚀 Weiterleitung ans Frontend
            return $this->redirect($redirectUrl);
        } catch (IdentityProviderException $e) {
            return new Response(
        'Azure Error:<br><br>' .
        nl2br(htmlspecialchars($e->getMessage())) .
        '<br><br>Raw Response:<br><br>' .
        nl2br(htmlspecialchars(print_r($e->getResponseBody(), true))),
        500
    );
        } catch (\Throwable $e) {
            return new Response('Allgemeiner Fehler: ' . $e->getMessage(), 500);
        }
    }
}
