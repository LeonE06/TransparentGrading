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
            if (!$request->get('code')) {
                return new Response('Kein "code" Parameter erhalten.', 400);
            }

            // Access Token anfordern
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            // ===========================
            // 🔥 DEBUG: JWT PAYLOAD ZEIGEN
            // ===========================
            $jwt = $token->getToken();

            $parts = explode('.', $jwt);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode($parts[1]), true);
                $pretty = json_encode($payload, JSON_PRETTY_PRINT);

                return new Response(
                    "<h1>DEBUG JWT PAYLOAD</h1>" .
                    "<b>JWT (raw):</b><br><textarea style='width:100%;height:200px'>$jwt</textarea><br><br>" .
                    "<b>Decoded Payload:</b><pre>$pretty</pre>",
                    500
                );
            }

            return new Response("Token konnte nicht dekodiert werden.", 500);

            // ===========================
            // AB HIER (wird erst aktiv, wenn debug raus ist)
            // ===========================

            /*
            $graphUser = $this->provider->get("https://graph.microsoft.com/v1.0/me", $token);
            $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'] ?? null;
            $vorname = $graphUser['givenName'] ?? 'Unbekannt';
            $nachname = $graphUser['surname'] ?? 'Unbekannt';

            if (!$email) {
                return new Response('Keine gültige E-Mail-Adresse erhalten.', 400);
            }

            $redirectUrl = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

            return $this->redirect($redirectUrl);
            */

        } catch (IdentityProviderException $e) {
            $body = method_exists($e, 'getResponseBody')
                ? print_r($e->getResponseBody(), true)
                : 'No body available';

            return new Response(
                "Azure Error:<br><br>" .
                nl2br(htmlspecialchars($e->getMessage())) .
                "<br><br>Raw Response:<br><pre>$body</pre>",
                500
            );
        } catch (\Throwable $e) {
            return new Response('Allgemeiner Fehler: ' . $e->getMessage(), 500);
        }
    }
}
