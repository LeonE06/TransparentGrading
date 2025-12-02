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

        $clientId = $_ENV['AZURE_CLIENT_ID'] ?? $_SERVER['AZURE_CLIENT_ID'];
        $clientSecret = $_ENV['AZURE_CLIENT_SECRET'] ?? $_SERVER['AZURE_CLIENT_SECRET'];
        $tenant = $_ENV['AZURE_TENANT_ID'] ?? $_SERVER['AZURE_TENANT_ID'];
        $redirectUri = $_ENV['AZURE_REDIRECT_URI'] ?? $_SERVER['AZURE_REDIRECT_URI'];

        $this->provider = new Azure([
            'clientId'      => $clientId,
            'clientSecret'  => $clientSecret,
            'tenant'        => $tenant,
            'redirectUri'   => $redirectUri,
            'resource'      => 'https://graph.microsoft.com/',   // ❗ WICHTIG!
            'debug'         => false,
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
                ],
            ]);

            // CSRF-Schutz
            $state = $this->provider->getState();
            $_SESSION['azure_oauth_state'] = $state;

            return $this->redirect($authUrl);

        } catch (\Throwable $e) {
            return new Response("Login-Fehler: " . $e->getMessage(), 500);
        }
    }

    #[Route('/auth', name: 'auth_alias', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        try {
            if (!$request->get('code')) {
                return new Response('Kein Code erhalten.', 400);
            }

            // State prüfen
            if ($request->get('state') !== ($_SESSION['azure_oauth_state'] ?? null)) {
                return new Response("Ungültiger OAuth-State.", 400);
            }

            // Token holen
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            // Graph /me Request
            $graphUser = $this->provider->get("https://graph.microsoft.com/v1.0/me", $token);

            $email = $graphUser['mail']
                ?? $graphUser['userPrincipalName']
                ?? null;

            $vorname = $graphUser['givenName'] ?? '';
            $nachname = $graphUser['surname'] ?? '';

            if (!$email) {
                return new Response("Microsoft lieferte keine Email zurück.", 500);
            }

            $redirectUrl = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

            return $this->redirect($redirectUrl);

        } catch (IdentityProviderException $e) {
            return new Response("Azure-Fehler: " . $e->getMessage(), 500);

        } catch (\Throwable $e) {
            return new Response("Allgemeiner Fehler: " . $e->getMessage(), 500);
        }
    }
}
