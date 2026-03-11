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

    public function __construct(private MicrosoftUserService $userService)
    {
        $this->provider = new Azure([
            'clientId'     => $_ENV['AZURE_CLIENT_ID'],
            'clientSecret' => $_ENV['AZURE_CLIENT_SECRET'],
            'tenant'       => $_ENV['AZURE_TENANT_ID'] . '/v2.0',
            'redirectUri'  => $_ENV['AZURE_REDIRECT_URI'],
            'resource'     => 'https://graph.microsoft.com',
            'debug'        => false,
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
                    'email',
                    'https://graph.microsoft.com/User.Read',
                ],
                'disableState' => true,
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
        $code = $request->query->get('code');
        if (!$code) {
            return new Response('Kein Code erhalten', 400);
        }

        $tokenMicrosoft = $this->provider->getAccessToken('authorization_code', [
            'code'         => $code,
            'disableState' => true,
        ]);

        // User von Microsoft holen
        $graphUser = $this->provider->get('https://graph.microsoft.com/v1.0/me', $tokenMicrosoft);

        // Echte Login-Adresse beeinflusst Rolle
        $email = $graphUser['userPrincipalName'] ?? $graphUser['mail'] ?? '';
        $local = explode('@', strtolower($email))[0];

        // Falls Hauptadresse kein Schüler → Proxy-Aliase prüfen
        if (!preg_match('/^[0-9]{4}$/', $local) && isset($graphUser['proxyAddresses'])) {
            foreach ($graphUser['proxyAddresses'] as $address) {
                $address = strtolower(str_replace('smtp:', '', $address));
                $localAlias = explode('@', $address)[0];

                if (preg_match('/^[0-9]{4}$/', $localAlias)) {
                    $email = $address;
                    break;
                }
            }
        }

        $vorname  = $graphUser['givenName'] ?? '';
        $nachname = $graphUser['surname'] ?? '';

        // Now, find the user in the database
        $existingUser = $this->userService->findOrCreateUser($vorname, $nachname, $email); // Assuming you have a method in your service to handle this

        // Determine if the user is an admin by checking the database
        $isAdmin = $existingUser->getIsAdmin(); // Assuming `is_admin` is a boolean field in your database

        // Determine role (this can be based on your logic from the `handleMicrosoftUser` method)
        $role = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

        // JWT payload
        $payload = [
            'email'    => $email,
            'role'     => $role,
            'is_admin' => $isAdmin,  // Include the is_admin flag in the payload
            'exp'      => time() + 3600, // 1 hour expiration
        ];

        // Encode the JWT
        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        // Redirect to frontend with the token
        $frontendUrl = $_ENV['FRONTEND_URL'];
        return $this->redirect($frontendUrl . '/auth/callback?token=' . $jwt);
    } catch (\Throwable $e) {
        return new Response('Fehler: ' . $e->getMessage(), 500);
    }
}
}