<?php

namespace App\Controller;

use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer')]
class TeacherAuthentificationController extends AbstractController
{
    #[Route('/klassen', name: 'api_lehrer_klassen', methods: ['GET'])]
    public function klassen(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Nicht authentifiziert'], 401);
        }

        // ✅ MS365 User holen
        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) {
            return $this->json(['error' => 'MS365 User nicht gefunden'], 404);
        }

        // ✅ Lehrer holen (je nach Mapping: ms365User oder MS365Usr)
        $lehrer = $em->getRepository(Lehrer::class)->findOneBy(['ms365User' => $ms365User]);
        if (!$lehrer) {
            $lehrer = $em->getRepository(Lehrer::class)->findOneBy(['MS365Usr' => $ms365User]); // Fallback
        }
        if (!$lehrer) {
            return $this->json(['error' => 'Lehrer nicht gefunden'], 404);
        }

        $conn = $em->getConnection();

        // ✅ Nur Klassen, wo Kurse.lehrer_id = Lehrer.id
        $rows = $conn->fetchAllAssociative(
            "SELECT DISTINCT k.id, k.name
             FROM Kurse ku
             JOIN Klassen k ON k.id = ku.klasse_id
             WHERE ku.lehrer_id = ?
             ORDER BY k.name ASC",
            [$lehrer->getId()]
        );

        return $this->json($rows);
    }
}
