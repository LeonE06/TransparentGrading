<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/lehrer')]
class LehrerKlassenController extends AbstractController
{
    #[Route('/klassen', name: 'api_lehrer_klassen', methods: ['GET'])]
    public function klassen(Connection $conn): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_LEHRER');

        // 1) eingeloggten User holen
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return $this->json(['error' => 'Nicht eingeloggt'], 401);
        }

        $ms365UserId = (int) $user->getId();

        // 2) Lehrer-ID über MS365Usr_ID bestimmen
        $lehrerId = $conn->fetchOne(
            "SELECT id
             FROM Lehrer
             WHERE MS365Usr_ID = :uid
             LIMIT 1",
            ['uid' => $ms365UserId]
        );

        if (!$lehrerId) {
            return $this->json(['error' => 'Lehrerprofil nicht gefunden'], 403);
        }

        // 3) Klassen holen, die dieser Lehrer über Kurse unterrichtet
        $rows = $conn->fetchAllAssociative(
            "SELECT DISTINCT
                k.id,
                k.name
             FROM Kurse ku
             JOIN Klassen k ON k.id = ku.klasse_id
             WHERE ku.lehrer_id = :lehrerId
             ORDER BY k.name ASC",
            ['lehrerId' => (int) $lehrerId]
        );

        return $this->json($rows);
    }
}
