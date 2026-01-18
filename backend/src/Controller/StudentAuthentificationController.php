<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/schueler')]
class StudentAuthentificationController extends AbstractController
{
    #[Route('/me', methods: ['GET'])]
    public function getCurrentStudent(
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }
        $ms365User = $em->getRepository(\App\Entity\Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);
        $schueler = $em->getRepository(\App\Entity\Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);

        if (!$schueler) {
            return new JsonResponse(['error' => 'Schüler nicht gefunden'], 404);
        }

        // Einstellungen explizit laden, falls vorhanden
        $einstellungen = $em->getRepository(\App\Entity\Einstellungen::class)
            ->find($schueler->getId());

        if ($einstellungen) {
            $schueler->setEinstellungen($einstellungen);
        }

        $json = $serializer->serialize($schueler, 'json', [
            'groups' => ['student_read'],
        ]);

        return new JsonResponse($json, 200, [], true);
    }
}