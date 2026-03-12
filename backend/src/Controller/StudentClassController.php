<?php

namespace App\Controller;

use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/schueler')]
class StudentClassController extends AbstractController
{
    private function getCurrentSchueler(EntityManagerInterface $em): ?Schueler
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) {
            return null;
        }

        return $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);
    }

    #[Route('/faecher', methods: ['GET'])]
    public function getFaecher(EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->getCurrentSchueler($em);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        $schuelerId = $schueler->getId();

        $rows = $em->getConnection()->executeQuery("
            SELECT 
                k.id AS kurs_id,
                f.name AS fach_name,
                c.name AS klasse_name,
                COALESCE(ke.benachrichtigung, 1) AS notif_enabled,
                COALESCE(ke.sichtbar, 1) AS sichtbar,
                ROUND(
                    SUM(
                        CASE
                            WHEN n.note IS NOT NULL AND n.gewichtung > 0 THEN n.note * n.gewichtung
                            ELSE 0
                        END
                    ) / NULLIF(
                        SUM(
                            CASE
                                WHEN n.note IS NOT NULL AND n.gewichtung > 0 THEN n.gewichtung
                                ELSE 0
                            END
                        ),
                        0
                    ),
                    2
                ) AS gesamtnote,
                COUNT(n.note) AS anzahl_noten
            FROM Kurs_Schueler ks
            JOIN Kurse k ON ks.kurs_id = k.id
            JOIN Faecher f ON f.id = k.fach_id
            LEFT JOIN Klassen c ON c.id = k.klasse_id
            LEFT JOIN Kurs_Einstellungen ke
                ON ke.kurs_id = k.id AND ke.schueler_id = ks.schueler_id
            LEFT JOIN (
                SELECT
                    ku.id AS kurs_id,
                    b.schueler_id,
                    b.note,
                    COALESCE(ba.gewichtung, 0) AS gewichtung
                FROM Benotung b
                INNER JOIN Kurse ku
                    ON ku.fach_id = b.fach_id
                   AND ku.lehrer_id = b.lehrer_id
                LEFT JOIN Benotungsarten ba ON ba.id = b.typ

                UNION ALL

                SELECT
                    a.kurs_id AS kurs_id,
                    ab.schueler_id,
                    ab.note,
                    COALESCE(a.gewichtung_prozent, ba.gewichtung, 0) AS gewichtung
                FROM Aufgaben_Bewertung ab
                INNER JOIN Aufgaben a ON a.id = ab.aufgabe_id
                LEFT JOIN Benotungsarten ba ON ba.id = a.benotungsart_id
            ) n
                ON n.kurs_id = k.id
               AND n.schueler_id = ks.schueler_id
            WHERE ks.schueler_id = :sid
            GROUP BY
                k.id,
                f.name,
                c.name,
                ke.benachrichtigung,
                ke.sichtbar
            ORDER BY f.name ASC
        ", [
            'sid' => $schuelerId
        ])->fetchAllAssociative();

        $mapped = array_map(static function (array $row): array {
            return [
                'kurs_id' => (int) $row['kurs_id'],
                'fach_name' => $row['fach_name'],
                'klasse_name' => $row['klasse_name'],
                'notif_enabled' => (int) $row['notif_enabled'],
                'sichtbar' => (int) $row['sichtbar'],
                'gesamtnote' => $row['gesamtnote'] !== null ? (float) $row['gesamtnote'] : null,
                'anzahl_noten' => (int) $row['anzahl_noten'],
            ];
        }, $rows);

        return new JsonResponse($mapped);
    }

    #[Route('/faecher/{kursId}/toggle-visibility', methods: ['PUT'])]
    public function toggleVisibility(int $kursId, EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->getCurrentSchueler($em);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        $sql = "
            INSERT INTO Kurs_Einstellungen (schueler_id, kurs_id, sichtbar)
            VALUES (:sid, :kid, 0)
            ON DUPLICATE KEY UPDATE sichtbar = NOT sichtbar
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schueler->getId(),
            'kid' => $kursId
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/faecher/{kursId}/toggle-notif', methods: ['PUT'])]
    public function toggleNotif(int $kursId, EntityManagerInterface $em): JsonResponse
    {
        $schueler = $this->getCurrentSchueler($em);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        $sql = "
            INSERT INTO Kurs_Einstellungen (schueler_id, kurs_id, benachrichtigung)
            VALUES (:sid, :kid, 0)
            ON DUPLICATE KEY UPDATE benachrichtigung = NOT benachrichtigung
        ";

        $em->getConnection()->executeStatement($sql, [
            'sid' => $schueler->getId(),
            'kid' => $kursId
        ]);

        return new JsonResponse(['status' => 'ok']);
    }
}
