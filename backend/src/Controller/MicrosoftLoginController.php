<?php

namespace App\Controller;

use App\Service\MicrosoftUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MicrosoftLoginController extends AbstractController
{
    public function __construct(
        private MicrosoftUserService $msUserService,
        private string $frontendUrl = "https://transparent-grading-flax.vercel.app"
    ) {}

    #[Route('/microsoft', name: 'app_microsoft_login')]
    public function login(): Response
    {
        return $this->redirect($this->msUserService->getAuthUrl());
    }

    #[Route('/auth', name: 'app_microsoft_callback')]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');

        if (!$code) {
            return new Response("OAuth Code fehlt!", 400);
        }

        try {
            $jwt = $this->msUserService->authenticateAndCreateToken($code);
            return $this->redirect($this->frontendUrl . "/auth/callback?token=" . $jwt);

        } catch (\Throwable $e) {
            return new Response("Fehler: " . $e->getMessage(), 500);
        }
    }
}
