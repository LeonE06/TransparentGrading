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
        // Optional, aber sehr empfohlen
        $this->denyAccessUnlessGranted('ROLE_LEHRER');

        $klasseId = (int) $request->query->get('klasseId', 0);
        $range = $request->query->get('range', 'daily'); // daily | weekly | monthly

        if ($klasseId <= 0) {
            return $this->json([
                'error' => 'klasseId fehlt oder ist ungültig'
            ], 400);
        }

        [$sql] = match ($range) {
            'weekly' => [
                "
                SELECT
                  DATE_FORMAT(md.day, '%x-W%v') AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = :klasseId
                GROUP BY YEARWEEK(md.day, 1)
                ORDER BY YEARWEEK(md.day, 1)
                "
            ],
            'monthly' => [
                "
                SELECT
                  DATE_FORMAT(md.day, '%Y-%m') AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = :klasseId
                GROUP BY YEAR(md.day), MONTH(md.day)
                ORDER BY YEAR(md.day), MONTH(md.day)
                "
            ],
            default => [
                "
                SELECT
                  md.day AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = :klasseId
                GROUP BY md.day
                ORDER BY md.day
                "
            ],
        };

        $rows = $conn->fetchAllAssociative($sql, [
            'klasseId' => $klasseId
        ]);

        $labels = array_map(fn ($r) => (string) $r['label'], $rows);
        $values = array_map(fn ($r) => (float) $r['avg_mood'], $rows);

        $overallAvg = count($values)
            ? round(array_sum($values) / count($values), 2)
            : null;

        return $this->json([
            'klasse' => [
                'id' => $klasseId
            ],
            'range' => $range,
            'labels' => $labels,
            'values' => $values,
            'overall_avg' => $overallAvg
        ]);
    }
}
