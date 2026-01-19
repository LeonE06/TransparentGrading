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
        // 1️⃣ eingeloggten User holen
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Nicht authentifiziert'], 401);
        }

        // 2️⃣ Lehrer-ID über MS365 User bestimmen
        $lehrerId = $conn->fetchOne(
            "SELECT id
             FROM Lehrer
             WHERE MS365Usr_ID = :uid
             LIMIT 1",
            ['uid' => $user->getId()]
        );

        if (!$lehrerId) {
            return $this->json(['error' => 'Lehrer nicht gefunden'], 403);
        }

        // 3️⃣ Klassen über UNION holen (Klassen + Kurse)
        $rows = $conn->fetchAllAssociative(
            "
            SELECT DISTINCT k.id, k.name
            FROM Klassen k
            WHERE k.lehrer_id = :lehrerId

            UNION DISTINCT

            SELECT DISTINCT k2.id, k2.name
            FROM Kurse ku
            JOIN Klassen k2 ON k2.id = ku.klasse_id
            WHERE ku.lehrer_id = :lehrerId

            ORDER BY name ASC
            ",
            ['lehrerId' => (int) $lehrerId]
        );

        return $this->json($rows);
    }
}