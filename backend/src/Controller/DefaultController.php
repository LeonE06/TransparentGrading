<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController
{
    #[Route('/', name: 'api_root')]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'message' => 'TransparentGrading API laeuft',
        ]);
    }
}
