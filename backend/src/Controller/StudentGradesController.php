<?php

namespace App\Controller;

use App\Entity\Kurse;
use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api')]
class StudentGradesController extends AbstractController
{
    private function calculateWeightedAverage(array $rows): ?float
    {
        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($rows as $row) {
            if (!isset($row['note']) || $row['note'] === null) {
                continue;
            }

            $weight = isset($row['gewichtung']) ? (float) $row['gewichtung'] : 0.0;
            if ($weight <= 0.0) {
                continue;
            }

            $weightedSum += (float) $row['note'] * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return null;
        }

        return $weightedSum / $totalWeight;
    }

    private function getCurrentSchueler(EntityManagerInterface $em): ?Schueler
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        // Microsoft365User über die E-Mail (UserIdentifier) finden
        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) {
            return null;
        }

        // Schueler über Relation ms365User finden
        return $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);
    }

    #[Route('/schueler/faecher/{kursId}/noten', methods: ['GET'])]
    public function getNoten(
        int $kursId,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {

        // 🔒 Login erforderlich (Microsoft via Symfony Security)
        $schueler = $this->getCurrentSchueler($em);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Not authorized'], 401);
        }

        // ------------------------------------------------
        // 1) Hole alle Noten zu diesem Fach/Kurs
        // ------------------------------------------------
        $noten = $em->getConnection()->executeQuery("
            SELECT
                CONCAT('benotung-', b.id) AS id,
                DATE_FORMAT(b.datum, '%Y-%m-%d') AS datum,
                b.note,
                ba.name AS typ_name,
                COALESCE(ba.gewichtung, 0) AS gewichtung,
                b.kommentar,
                NULL AS punkte,
                NULL AS max_punkte,
                NULL AS prozent
            FROM Benotung b
            LEFT JOIN Benotungsarten ba ON ba.id = b.typ
            WHERE b.schueler_id = :sid
              AND b.fach_id = (SELECT fach_id FROM Kurse WHERE id = :kid)

            UNION ALL

            SELECT
                CONCAT('aufgabe-', ab.id) AS id,
                DATE_FORMAT(COALESCE(ab.datum, a.faelligkeit), '%Y-%m-%d') AS datum,
                ab.note,
                COALESCE(ba.name, a.titel) AS typ_name,
                COALESCE(a.gewichtung_prozent, ba.gewichtung, 0) AS gewichtung,
                COALESCE(ab.kommentar, a.kommentar, a.titel) AS kommentar,
                ab.punkte,
                a.max_punkte,
                CASE
                    WHEN ab.punkte IS NOT NULL AND a.max_punkte IS NOT NULL AND a.max_punkte > 0
                        THEN ROUND((ab.punkte / a.max_punkte) * 100, 1)
                    ELSE NULL
                END AS prozent
            FROM Aufgaben_Bewertung ab
            INNER JOIN Aufgaben a ON a.id = ab.aufgabe_id
            LEFT JOIN Benotungsarten ba ON ba.id = a.benotungsart_id
            WHERE ab.schueler_id = :sid
              AND a.kurs_id = :kid

            ORDER BY datum ASC
        ", [
            'sid' => $schueler->getId(),
            'kid' => $kursId
        ])->fetchAllAssociative();

        // ------------------------------------------------
        // 2) Berechne Schüler-Durchschnitt
        // ------------------------------------------------
        $schuelerDurchschnitt = $this->calculateWeightedAverage($noten);

        // ------------------------------------------------
        // 3) Berechne Klassenschnitt
        // ------------------------------------------------
        $klassenNoten = $em->getConnection()->executeQuery("
            SELECT
                b.note,
                COALESCE(ba.gewichtung, 0) AS gewichtung
            FROM Benotung b
            LEFT JOIN Benotungsarten ba ON ba.id = b.typ
            WHERE b.fach_id = (SELECT fach_id FROM Kurse WHERE id = :kid)

            UNION ALL

            SELECT
                ab.note,
                COALESCE(a.gewichtung_prozent, ba.gewichtung, 0) AS gewichtung
            FROM Aufgaben_Bewertung ab
            INNER JOIN Aufgaben a ON a.id = ab.aufgabe_id
            LEFT JOIN Benotungsarten ba ON ba.id = a.benotungsart_id
            WHERE a.kurs_id = :kid
        ", [
            'kid' => $kursId
        ])->fetchAllAssociative();
        $klassenDurchschnitt = $this->calculateWeightedAverage($klassenNoten);

        // ------------------------------------------------
        // 4) JSON Rückgabe
        // ------------------------------------------------
        return new JsonResponse([
            'noten' => $noten,
            'schueler_notenstand' => $schuelerDurchschnitt !== null ? round($schuelerDurchschnitt, 2) : null,
            'klassenschnitt' => $klassenDurchschnitt !== null ? round($klassenDurchschnitt, 2) : null,
        ]);
    }
}
