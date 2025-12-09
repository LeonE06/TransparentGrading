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

            $graphUser = $this->provider->get('https://graph.microsoft.com/v1.0/me', $tokenMicrosoft);

            $email = strtolower($graphUser['userPrincipalName'] ?? $graphUser['mail'] ?? '');
            $proxyAddresses = $graphUser['proxyAddresses'] ?? [];

            $studentEmail = null;
            $teacherEmail = null;

            foreach ($proxyAddresses as $address) {
                $address = strtolower(str_replace('smtp:', '', $address));
                $local = explode('@', $address)[0];

                if (preg_match('/^[0-9]{4}$/', $local)) {
                    $studentEmail = $address;
                }

                if (preg_match('/^[a-z]{3}$/', $local)) {
                    $teacherEmail = $address;
                }
            }

            if ($studentEmail) {
                $email = $studentEmail;
            } elseif ($teacherEmail) {
                $email = $teacherEmail;
            }

            $vorname  = $graphUser['givenName'] ?? '';
            $nachname = $graphUser['surname'] ?? '';

            $role = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

            $payload = [
                'email' => $email,
                'role'  => $role,
                'exp'   => time() + 3600 // 1h gültig
            ];

            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            return $this->redirect($_ENV['FRONTEND_URL'] . '/auth/callback?token=' . $jwt);

        } catch (\Throwable $e) {
            return new Response('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
