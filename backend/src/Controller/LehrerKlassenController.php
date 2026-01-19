<?php

namespace App\Controller;

use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer')]
class LehrerKlassenController extends AbstractController
{
    #[Route('/klassen', name: 'api_lehrer_klassen', methods: ['GET'])]
    public function klassen(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Nicht authentifiziert'], 401);

        // ✅ wie bei Schülern: Email -> MS365User
        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) return $this->json(['error' => 'MS365 User nicht gefunden'], 404);

        // ✅ Lehrer finden: entweder Relation ms365User ODER über MS365Usr_ID
        $lehrer = $em->getRepository(Lehrer::class)->findOneBy(['ms365User' => $ms365User]);
        if (!$lehrer) {
            // falls dein Lehrer-Entity keine Relation hat, dann so:
            $lehrer = $em->getRepository(Lehrer::class)->findOneBy(['MS365Usr' => $ms365User]);
        }
        if (!$lehrer) {
            // fallback: direkt per ID (MS365Usr_ID entspricht tbl_Microsoft365_User.id)
            $lehrer = $em->getRepository(Lehrer::class)->findOneBy(['MS365Usr_ID' => $ms365User->getId()]);
        }

        if (!$lehrer) return $this->json(['error' => 'Lehrer nicht gefunden'], 404);

        $conn = $em->getConnection();

        // ✅ UNION: Klassen.lehrer_id ODER Kurse.lehrer_id
        $rows = $conn->fetchAllAssociative(
            "
            SELECT DISTINCT k.id, k.name
            FROM Klassen k
            WHERE k.lehrer_id = ?

            UNION DISTINCT

            SELECT DISTINCT k2.id, k2.name
            FROM Kurse ku
            JOIN Klassen k2 ON k2.id = ku.klasse_id
            WHERE ku.lehrer_id = ?

            ORDER BY name ASC
            ",
            [$lehrer->getId(), $lehrer->getId()]
        );

        return $this->json($rows);
    }
}
