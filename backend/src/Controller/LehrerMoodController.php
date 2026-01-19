<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/lehrer')]
class LehrerMoodController extends AbstractController
{
    #[Route('/mood', name: 'api_lehrer_mood', methods: ['GET'])]
    public function mood(Request $request, Connection $conn): JsonResponse
    {
        // ✅ Nur Lehrer dürfen hier rein
        $this->denyAccessUnlessGranted('ROLE_LEHRER');

        $klasseId = (int) $request->query->get('klasseId', 0);
        $range = (string) $request->query->get('range', 'daily'); // daily | weekly | monthly

        if ($klasseId <= 0) {
            return $this->json(['error' => 'klasseId fehlt oder ist ungültig'], 400);
        }

        // ✅ Eingeloggten User holen (tbl_Microsoft365_User)
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return $this->json(['error' => 'Nicht eingeloggt'], 401);
        }

        $ms365UserId = (int) $user->getId();

        // ✅ Lehrer-ID eindeutig über MS365Usr_ID ermitteln
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

        // ✅ Zugriff prüfen: Unterichtet dieser Lehrer diese Klasse? (Tabelle Kurse)
        $hasAccess = $conn->fetchOne(
            "SELECT 1
             FROM Kurse
             WHERE lehrer_id = :lehrerId
               AND klasse_id = :klasseId
             LIMIT 1",
            [
                'lehrerId' => (int) $lehrerId,
                'klasseId' => $klasseId,
            ]
        );

        if (!$hasAccess) {
            return $this->json(['error' => 'Kein Zugriff auf diese Klasse'], 403);
        }

        // ✅ Mood-Daten aus View mood_daily aggregieren
        $sql = match ($range) {
            'weekly' => "
                SELECT
                  DATE_FORMAT(md.day, '%x-W%v') AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN Schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = :klasseId
                GROUP BY YEARWEEK(md.day, 1)
                ORDER BY YEARWEEK(md.day, 1)
            ",
            'monthly' => "
                SELECT
                  DATE_FORMAT(md.day, '%Y-%m') AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN Schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = :klasseId
                GROUP BY YEAR(md.day), MONTH(md.day)
                ORDER BY YEAR(md.day), MONTH(md.day)
            ",
            default => "
                SELECT
                  md.day AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN Schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = :klasseId
                GROUP BY md.day
                ORDER BY md.day
            ",
        };

        $rows = $conn->fetchAllAssociative($sql, ['klasseId' => $klasseId]);

        $labels = array_map(fn ($r) => (string) $r['label'], $rows);
        $values = array_map(fn ($r) => (float) $r['avg_mood'], $rows);

        $overallAvg = count($values)
            ? round(array_sum($values) / count($values), 2)
            : null;

        return $this->json([
            'klasse' => ['id' => $klasseId],
            'range' => $range,
            'labels' => $labels,
            'values' => $values,
            'overall_avg' => $overallAvg,
        ]);
    }
}
