<?php

namespace App\Controller;

use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer', name: 'api_lehrer_')]
class TeacherMeController extends AbstractController
{
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(EntityManagerInterface $em): JsonResponse
    {
        $jwtUser = $this->getUser();
        if (!$jwtUser) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $email = method_exists($jwtUser, 'getUserIdentifier') ? $jwtUser->getUserIdentifier() : null;
        if (!$email) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $m365 = $em->getRepository(Microsoft365User::class)->findOneBy(['email' => $email]);
        if (!$m365) {
            return new JsonResponse(['error' => 'Lehrer nicht gefunden'], 404);
        }

        $lehrer = $em->getRepository(Lehrer::class)->findOneBy(['ms365User' => $m365]);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Lehrer nicht gefunden'], 404);
        }

        return new JsonResponse([
            'id' => $lehrer->getId(),
            'vorname' => $lehrer->getVorname(),
            'nachname' => $lehrer->getNachname(),
            'email' => $m365->getEmail(),
        ]);
    }
}

