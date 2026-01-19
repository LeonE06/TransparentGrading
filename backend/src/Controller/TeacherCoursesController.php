<?php

namespace App\Controller;

use App\Entity\Benotungsarten;
use App\Entity\Kurse;
use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer', name: 'api_lehrer_courses_')]
class TeacherCoursesController extends AbstractController
{
    private function resolveLehrer(EntityManagerInterface $em): ?Lehrer
    {
        $jwtUser = $this->getUser();
        if (!$jwtUser || !method_exists($jwtUser, 'getUserIdentifier')) {
            return null;
        }

        $email = $jwtUser->getUserIdentifier();
        $m365 = $em->getRepository(Microsoft365User::class)->findOneBy(['email' => $email]);
        if (!$m365) {
            return null;
        }

        return $em->getRepository(Lehrer::class)->findOneBy(['ms365usr' => $m365]);
    }

    #[Route('/faecher', name: 'my_courses', methods: ['GET'])]
    public function myCourses(EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $rows = $em->getConnection()->executeQuery(
            "SELECT
                k.id AS kurs_id,
                k.name AS kurs_name,
                f.name AS fach_name,
                c.name AS klasse_name
             FROM Kurse k
             INNER JOIN Faecher f ON f.id = k.fach_id
             LEFT JOIN Klassen c ON c.id = k.klasse_id
             WHERE k.lehrer_id = :lid
             ORDER BY k.name ASC",
            ['lid' => $lehrer->getId()]
        )->fetchAllAssociative();

        $mapped = array_map(function (array $r) {
            $jahrgang = null;
            if (preg_match('/^(\\d{4}\\/\\d{2})\\s+/', $r['kurs_name'] ?? '', $m)) {
                $jahrgang = $m[1];
            }
            return [
                'id' => (int) $r['kurs_id'],
                'name' => $r['kurs_name'],
                'jahrgang' => $jahrgang,
                'fach' => $r['fach_name'],
                'klasse' => $r['klasse_name'],
            ];
        }, $rows);

        return new JsonResponse($mapped);
    }

    #[Route('/faecher/{kursId<\\d+>}', name: 'course_detail', methods: ['GET'])]
    public function courseDetail(int $kursId, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $row = $em->getConnection()->executeQuery(
            "SELECT
                k.id AS kurs_id,
                k.name AS kurs_name,
                f.name AS fach_name,
                c.name AS klasse_name
             FROM Kurse k
             INNER JOIN Faecher f ON f.id = k.fach_id
             LEFT JOIN Klassen c ON c.id = k.klasse_id
             WHERE k.id = :kid AND k.lehrer_id = :lid",
            ['kid' => $kursId, 'lid' => $lehrer->getId()]
        )->fetchAssociative();

        if (!$row) {
            return new JsonResponse(['error' => 'Kurs nicht gefunden'], 404);
        }

        $jahrgang = null;
        if (preg_match('/^(\\d{4}\\/\\d{2})\\s+/', $row['kurs_name'] ?? '', $m)) {
            $jahrgang = $m[1];
        }

        return new JsonResponse([
            'id' => (int) $row['kurs_id'],
            'name' => $row['kurs_name'],
            'jahrgang' => $jahrgang,
            'fach' => $row['fach_name'],
            'klasse' => $row['klasse_name'],
        ]);
    }

    #[Route('/faecher/{kursId<\\d+>}/uebersicht', name: 'course_overview', methods: ['GET'])]
    public function courseOverview(int $kursId, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $conn = $em->getConnection();

        $courseOk = $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurse WHERE id = :kid AND lehrer_id = :lid",
            ['kid' => $kursId, 'lid' => $lehrer->getId()]
        );
        if ((int) $courseOk === 0) {
            return new JsonResponse(['error' => 'Kurs nicht gefunden'], 404);
        }

        $studentsTotal = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurs_Schueler WHERE kurs_id = :kid",
            ['kid' => $kursId]
        );

        $avgNote = $conn->fetchOne(
            "SELECT AVG(ab.note) AS avg_note
             FROM Aufgaben a
             LEFT JOIN Aufgaben_Bewertung ab ON ab.aufgabe_id = a.id
             WHERE a.kurs_id = :kid",
            ['kid' => $kursId]
        );
        $avgNote = $avgNote !== null ? (float) $avgNote : null;

        $avgPct = null;
        if ($avgNote !== null) {
            $avgPct = 100 - (($avgNote - 1) / 4) * 100;
            $avgPct = max(0, min(100, $avgPct));
        }

        $assessmentsCount = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Aufgaben WHERE kurs_id = :kid",
            ['kid' => $kursId]
        );

        $participationAvg = null;
        if ($studentsTotal > 0 && $assessmentsCount > 0) {
            $participantsTotal = (int) $conn->fetchOne(
                "SELECT COUNT(*) FROM (
                    SELECT a.id, COUNT(DISTINCT ab.schueler_id) AS participants
                    FROM Aufgaben a
                    LEFT JOIN Aufgaben_Bewertung ab ON ab.aufgabe_id = a.id
                    WHERE a.kurs_id = :kid
                    GROUP BY a.id
                ) t",
                ['kid' => $kursId]
            );
            // Fallback: wenn keine Bewertungen existieren, participationAvg bleibt 0/NULL.
            if ($participantsTotal === 0) {
                $participationAvg = 0.0;
            } else {
                $sumParticipants = (float) $conn->fetchOne(
                    "SELECT SUM(participants) FROM (
                        SELECT a.id, COUNT(DISTINCT ab.schueler_id) AS participants
                        FROM Aufgaben a
                        LEFT JOIN Aufgaben_Bewertung ab ON ab.aufgabe_id = a.id
                        WHERE a.kurs_id = :kid
                        GROUP BY a.id
                    ) t",
                    ['kid' => $kursId]
                );
                $participationAvg = ($sumParticipants / $assessmentsCount) / $studentsTotal * 100;
            }
        }

        $trendRows = $conn->fetchAllAssociative(
            "SELECT
                DATE_FORMAT(COALESCE(ab.datum, a.faelligkeit), '%Y-%m') AS ym,
                AVG(ab.note) AS avg_note
             FROM Aufgaben a
             LEFT JOIN Aufgaben_Bewertung ab ON ab.aufgabe_id = a.id
             WHERE a.kurs_id = :kid
               AND COALESCE(ab.datum, a.faelligkeit) IS NOT NULL
             GROUP BY ym
             ORDER BY ym ASC",
            ['kid' => $kursId]
        );

        $trend = array_map(static function (array $r): array {
            return [
                'ym' => $r['ym'],
                'avgNote' => $r['avg_note'] !== null ? round((float) $r['avg_note'], 2) : null,
            ];
        }, $trendRows);

        return new JsonResponse([
            'klassenschnitt' => $avgNote !== null ? round($avgNote, 2) : null,
            'klassenschnittProzent' => $avgPct !== null ? round($avgPct, 1) : null,
            'teilnahmequote' => $participationAvg !== null ? round($participationAvg, 1) : null,
            'trend' => $trend,
        ]);
    }

    #[Route('/faecher/{kursId<\\d+>}/benotungsarten', name: 'grading_types', methods: ['GET'])]
    public function gradingTypes(int $kursId, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $courseOk = $em->getConnection()->fetchOne(
            "SELECT COUNT(*) FROM Kurse WHERE id = :kid AND lehrer_id = :lid",
            ['kid' => $kursId, 'lid' => $lehrer->getId()]
        );
        if ((int) $courseOk === 0) {
            return new JsonResponse(['error' => 'Kurs nicht gefunden'], 404);
        }

        $types = $em->getRepository(Benotungsarten::class)->findBy([], ['name' => 'ASC']);
        $mapped = array_map(static function (Benotungsarten $t): array {
            return [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'gewichtung' => $t->getGewichtung(),
            ];
        }, $types);

        return new JsonResponse($mapped);
    }

    #[Route('/faecher/{kursId<\\d+>}/schueler', name: 'course_students', methods: ['GET'])]
public function courseStudents(int $kursId, EntityManagerInterface $em): JsonResponse
{
    $lehrer = $this->resolveLehrer($em);
    if (!$lehrer) {
        return new JsonResponse(['error' => 'Unauthenticated'], 401);
    }

    $conn = $em->getConnection();

    // Kurs gehört dem Lehrer?
    $courseOk = $conn->fetchOne(
        "SELECT COUNT(*) FROM Kurse WHERE id = :kid AND lehrer_id = :lid",
        ['kid' => $kursId, 'lid' => $lehrer->getId()]
    );
    if ((int) $courseOk === 0) {
        return new JsonResponse(['error' => 'Kurs nicht gefunden'], 404);
    }

    // Gesamtnoten pro Schüler (für diesen Kurs):
    // - Benotung: fachbezogen (Kurs -> fach_id) + nur Noten dieses Lehrers
    // - Aufgaben_Bewertung: kursbezogen (Aufgaben -> kurs_id) + nur Noten dieses Lehrers
    $rows = $conn->fetchAllAssociative(
        "SELECT
            s.id,
            s.vorname,
            s.nachname,
            c.name AS klasse,
            ROUND(AVG(n.note), 2) AS gesamtnote,
            COUNT(n.note) AS anzahl_noten
         FROM Kurs_Schueler ks
         INNER JOIN Schueler s ON s.id = ks.schueler_id
         LEFT JOIN Klassen c ON c.id = s.klasse_id
         LEFT JOIN (
            -- 1) Fachnoten (Benotung) passend zum Kursfach und diesem Lehrer
            SELECT
              b.schueler_id,
              b.note
            FROM Benotung b
            INNER JOIN Kurse ku ON ku.fach_id = b.fach_id
            WHERE ku.id = :kid
              AND b.lehrer_id = :lid

            UNION ALL

            -- 2) Aufgabenbewertungen (kursbezogen) von diesem Lehrer
            SELECT
              ab.schueler_id,
              ab.note
            FROM Aufgaben_Bewertung ab
            INNER JOIN Aufgaben a ON a.id = ab.aufgabe_id
            WHERE a.kurs_id = :kid
              AND ab.lehrer_id = :lid
         ) n ON n.schueler_id = s.id
         WHERE ks.kurs_id = :kid
         GROUP BY s.id, s.vorname, s.nachname, c.name
         ORDER BY s.nachname ASC, s.vorname ASC",
        ['kid' => $kursId, 'lid' => $lehrer->getId()]
    );

    $mapped = array_map(static function (array $r): array {
        return [
            'id' => (int) $r['id'],
            'vorname' => $r['vorname'],
            'nachname' => $r['nachname'],
            'klasse' => $r['klasse'],
            'gesamtnote' => $r['gesamtnote'] !== null ? (float) $r['gesamtnote'] : null,
            'anzahl_noten' => (int) $r['anzahl_noten'],
        ];
    }, $rows);

    return new JsonResponse($mapped);
}

    
}
