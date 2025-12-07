<?php

namespace App\Controller;

use App\Service\MicrosoftUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use TheNetworg\OAuth2\Client\Provider\Azure;
use Firebase\JWT\JWT;

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
            'tenant' => $_ENV['AZURE_TENANT_ID'] . '/v2.0',
            'redirectUri' => $_ENV['AZURE_REDIRECT_URI'],
            'resource' => 'https://graph.microsoft.com',
        ]);
    }

    #[Route('/microsoft', name: 'microsoft', methods: ['GET'])]
    public function login(Request $request): Response
    {
        $authUrl = $this->provider->getAuthorizationUrl([
            'scope' => [
                'openid',
                'profile',
                'offline_access',
                'email',
                'https://graph.microsoft.com/User.Read',
            ]
        ]);

        $session = $request->getSession();
        $session->set('oauth2state', $this->provider->getState());

        return $this->redirect($authUrl);
    }

    #[Route('/auth', name: 'auth_alias', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        $session = $request->getSession();

        if (!isset($_GET['state']) || $_GET['state'] !== $session->get('oauth2state')) {
            return new Response('Ungültiger OAuth-State', 400);
        }

        if (!$request->get('code')) {
            return new Response('Kein Code erhalten', 400);
        }

        $tokenMicrosoft = $this->provider->getAccessToken('authorization_code', [
            'code' => $request->get('code'),
        ]);

        $graphUser = $this->provider->get("https://graph.microsoft.com/v1.0/me", $tokenMicrosoft);

        $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'];
        $vorname = $graphUser['givenName'] ?? '';
        $nachname = $graphUser['surname'] ?? '';

        // Benutzer speichern → Rolle ermitteln
        $role = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

        // 🔐 JWT erstellen
        $payload = [
            "email" => $email,
            "role" => $role,
            "exp" => time() + 3600
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        // Token sicher als Cookie setzen (HTTP-only)
        $response = new Response();
        $response->headers->setCookie(
            Cookie::create('auth_token', $jwt, time() + 3600, '/', null, true, true, false, 'Strict')
        );

        $frontendBase = $_ENV['FRONTEND_URL'];

        // Rollenbasierte Weiterleitung
        $route = match ($role) {
            'Schueler' => '/schueler/faecher',
            'Lehrer' => '/lehrer/faecher',
            default => '/'
        };

        return $this->redirect($frontendBase . $route);
    }
}
