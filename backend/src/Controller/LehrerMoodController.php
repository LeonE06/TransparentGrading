<?php

namespace App\Controller;

use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer')]
class LehrerMoodController extends AbstractController
{
    #[Route('/mood', name: 'api_lehrer_mood', methods: ['GET'])]
    public function mood(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Nicht authentifiziert'], 401);
        }

        $klasseId = (int) $request->query->get('klasseId', 0);
        $range = (string) $request->query->get('range', 'daily'); // daily|weekly|monthly

        if ($klasseId <= 0) {
            return $this->json(['error' => 'klasseId fehlt oder ist ungültig'], 400);
        }

        // ✅ MS365 User holen
        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$ms365User) {
            return $this->json(['error' => 'MS365 User nicht gefunden'], 404);
        }

        // ✅ Lehrer holen (je nach Mapping)
        $lehrer = $em->getRepository(Lehrer::class)->findOneBy(['ms365User' => $ms365User]);
        if (!$lehrer) {
            $lehrer = $em->getRepository(Lehrer::class)->findOneBy(['MS365Usr' => $ms365User]); // Fallback
        }
        if (!$lehrer) {
            return $this->json(['error' => 'Lehrer nicht gefunden'], 404);
        }

        $conn = $em->getConnection();

        // ✅ Zugriff prüfen: Lehrer muss Klasse über Kurse unterrichten
        $hasAccess = $conn->fetchOne(
            "SELECT 1
             FROM Kurse
             WHERE lehrer_id = ?
               AND klasse_id = ?
             LIMIT 1",
            [$lehrer->getId(), $klasseId]
        );

        if (!$hasAccess) {
            return $this->json(['error' => 'Kein Zugriff auf diese Klasse'], 403);
        }

        // ✅ Query je Zeitraum
        $sql = match ($range) {
            'weekly' => "
                SELECT
                  DATE_FORMAT(md.day, '%x-W%v') AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN Schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = ?
                GROUP BY YEARWEEK(md.day, 1)
                ORDER BY YEARWEEK(md.day, 1)
            ",
            'monthly' => "
                SELECT
                  DATE_FORMAT(md.day, '%Y-%m') AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN Schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = ?
                GROUP BY YEAR(md.day), MONTH(md.day)
                ORDER BY YEAR(md.day), MONTH(md.day)
            ",
            default => "
                SELECT
                  md.day AS label,
                  ROUND(AVG(md.avg_score), 2) AS avg_mood
                FROM mood_daily md
                JOIN Schueler s ON s.id = md.schueler_id
                WHERE s.klasse_id = ?
                GROUP BY md.day
                ORDER BY md.day
            ",
        };

        $rows = $conn->fetchAllAssociative($sql, [$klasseId]);

        $labels = array_map(fn($r) => (string) $r['label'], $rows);
        $values = array_map(fn($r) => (float) $r['avg_mood'], $rows);
        $overallAvg = count($values) ? round(array_sum($values) / count($values), 2) : null;

        return $this->json([
            'klasse' => ['id' => $klasseId],
            'range' => $range,
            'labels' => $labels,
            'values' => $values,
            'overall_avg' => $overallAvg
        ]);
    }
}
