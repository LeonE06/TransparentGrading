<?php

namespace App\Controller;

use App\Entity\Benotungsarten;
use App\Entity\Faecher;
use App\Entity\Klassen;
use App\Entity\Kurse;
use App\Entity\Lehrer;
use App\Entity\LehrerFach;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer', name: 'api_lehrer_')]
class TeacherCoursesController extends AbstractController
{
    private function syncSubjectAssignments(EntityManagerInterface $em, Lehrer $lehrer): void
    {
        $em->getConnection()->executeStatement(
            "INSERT IGNORE INTO lehrer_fach (leher_id, fach_id)
             SELECT DISTINCT k.lehrer_id, k.fach_id
             FROM Kurse k
             WHERE k.lehrer_id = :lid
               AND k.fach_id IS NOT NULL",
            ['lid' => $lehrer->getId()]
        );
    }

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

        return $em->getRepository(Lehrer::class)->findOneBy(['ms365User' => $m365]);
    }

    /**
     * Liste der Kurse des Lehrers (Legacy: /faecher)
     */
    #[Route('/kurse', name: 'courses_list', methods: ['GET'])]
    #[Route('/faecher', name: 'courses_list_legacy', methods: ['GET'])]
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

    /**
     * Liste der Fächer des Lehrers über die Join-Tabelle lehrer_fach.
     */
    #[Route('/faecher-liste', name: 'subjects_list', methods: ['GET'])]
    public function mySubjects(EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        // Legacy-Kurse werden beim ersten Zugriff in die Join-Tabelle übernommen.
        $this->syncSubjectAssignments($em, $lehrer);

        $subjects = $em->getRepository(Faecher::class)->findForLehrer($lehrer);
        $rows = array_map(static function (Faecher $fach): array {
            return [
                'id' => $fach->getId(),
                'name' => $fach->getName(),
            ];
        }, $subjects);

        return new JsonResponse($rows);
    }

    /**
     * Schülersuche für Kurserstellung.
     */
    #[Route('/schueler/suche', name: 'students_search', methods: ['GET'])]
    public function searchStudents(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $limit = (int) $request->query->get('limit', 20);
        $limit = max(1, min($limit, 50));
        $term = '%' . mb_strtolower($query) . '%';

        $rows = $em->getConnection()->fetchAllAssociative(
            "SELECT
                s.id,
                s.vorname,
                s.nachname,
                c.name AS klasse
             FROM Schueler s
             LEFT JOIN Klassen c ON c.id = s.klasse_id
             WHERE LOWER(COALESCE(s.vorname, '')) LIKE :term
                OR LOWER(COALESCE(s.nachname, '')) LIKE :term
                OR LOWER(CONCAT(COALESCE(s.vorname, ''), ' ', COALESCE(s.nachname, ''))) LIKE :term
                OR LOWER(CONCAT(COALESCE(s.nachname, ''), ' ', COALESCE(s.vorname, ''))) LIKE :term
             ORDER BY s.nachname ASC, s.vorname ASC
             LIMIT :limit",
            ['term' => $term, 'limit' => $limit],
            ['limit' => \PDO::PARAM_INT]
        );

        $mapped = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'vorname' => $row['vorname'],
                'nachname' => $row['nachname'],
                'klasse' => $row['klasse'],
            ];
        }, $rows);

        return new JsonResponse($mapped);
    }

    /**
     * Kurs/Fach anlegen (Legacy: /faecher)
     */
    #[Route('/kurse', name: 'courses_create', methods: ['POST'])]
    #[Route('/faecher', name: 'courses_create_legacy', methods: ['POST'])]
    public function createCourse(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];

        $fachId = $data['fachId'] ?? null;
        $fachName = trim((string) ($data['fachName'] ?? $data['fach'] ?? ''));
        $kursName = trim((string) ($data['kursName'] ?? $data['name'] ?? ''));
        $klasseId = $data['klasseId'] ?? null;
        $studentIds = $data['studentIds'] ?? [];

        if (!is_array($studentIds)) {
            return new JsonResponse(['error' => 'studentIds muss ein Array sein'], 400);
        }
        $studentIds = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $studentIds
        ), static fn (int $id): bool => $id > 0)));

        if ($fachId === null && $fachName === '') {
            return new JsonResponse(['error' => 'fachName oder fachId sind Pflichtfelder'], 400);
        }

        $fach = null;
        if ($fachId !== null && $fachId !== '') {
            $fach = $em->getRepository(Faecher::class)->find((int) $fachId);
            if (!$fach) {
                return new JsonResponse(['error' => 'Ungültige fachId'], 400);
            }
        } else {
            $fach = $em->getRepository(Faecher::class)->findOneBy(['name' => $fachName]);
            if (!$fach) {
                $fach = new Faecher();
                $fach->setName($fachName);
                $em->persist($fach);
            }
        }

        $klasse = null;
        if ($klasseId !== null && $klasseId !== '') {
            $klasse = $em->getRepository(Klassen::class)->find((int) $klasseId);
            if (!$klasse) {
                return new JsonResponse(['error' => 'Ungültige klasseId'], 400);
            }
        }

        if ($kursName === '') {
            $kursName = $fach->getName();
        }

        $lehrerFach = null;
        if ($fach->getId() !== null) {
            $lehrerFach = $em->getRepository(LehrerFach::class)->findOneBy([
                'lehrer' => $lehrer,
                'fach' => $fach,
            ]);
        }
        if (!$lehrerFach) {
            $lehrerFach = new LehrerFach();
            $lehrerFach->setLehrer($lehrer);
            $lehrerFach->setFach($fach);
            $em->persist($lehrerFach);
        }

        $kurs = new Kurse();
        $kurs->setName($kursName);
        $kurs->setFach($fach);
        $kurs->setLehrer($lehrer);
        $kurs->setKlasse($klasse);

        $em->persist($kurs);
        $em->flush();

        $classStudentsAdded = 0;
        if ($klasse) {
            $classStudentsAdded = $em->getConnection()->executeStatement(
                "INSERT INTO Kurs_Schueler (kurs_id, schueler_id)
                 SELECT :kursId, s.id
                 FROM Schueler s
                 LEFT JOIN Kurs_Schueler ks
                   ON ks.kurs_id = :kursId AND ks.schueler_id = s.id
                 WHERE s.klasse_id = :klasseId
                   AND ks.schueler_id IS NULL",
                [
                    'kursId' => $kurs->getId(),
                    'klasseId' => $klasse->getId(),
                ]
            );
        }

        $manualStudentsAdded = 0;
        if ($studentIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($studentIds), '?'));
            $manualStudentsAdded = $em->getConnection()->executeStatement(
                "INSERT INTO Kurs_Schueler (kurs_id, schueler_id)
                 SELECT ?, s.id
                 FROM Schueler s
                 LEFT JOIN Kurs_Schueler ks
                   ON ks.kurs_id = ? AND ks.schueler_id = s.id
                 WHERE s.id IN ($placeholders)
                   AND ks.schueler_id IS NULL",
                array_merge([$kurs->getId(), $kurs->getId()], $studentIds)
            );
        }

        $studentsAdded = $classStudentsAdded + $manualStudentsAdded;

        return new JsonResponse([
            'id' => $kurs->getId(),
            'name' => $kurs->getName(),
            'fachId' => $fach->getId(),
            'fachName' => $fach->getName(),
            'klasseId' => $klasse ? $klasse->getId() : null,
            'klasseName' => $klasse ? $klasse->getName() : null,
            'studentsAdded' => $studentsAdded,
            'studentsAddedFromClass' => $classStudentsAdded,
            'studentsAddedIndividually' => $manualStudentsAdded,
        ], 201);
    }

    /**
     * Kurs löschen (Legacy: /faecher/{kursId})
     */
    #[Route('/kurse/{kursId<\\d+>}', name: 'courses_delete', methods: ['DELETE'])]
    #[Route('/faecher/{kursId<\\d+>}', name: 'courses_delete_legacy', methods: ['DELETE'])]
    public function deleteCourse(int $kursId, EntityManagerInterface $em): JsonResponse
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

        $conn->beginTransaction();
        try {
            $conn->executeStatement(
                "DELETE FROM Nachrichten_Status
                 WHERE nachricht_id IN (
                    SELECT id FROM Nachrichten WHERE kurs_id = :kid
                 )",
                ['kid' => $kursId]
            );

            $conn->executeStatement(
                "DELETE FROM Aufgaben_Bewertung
                 WHERE aufgabe_id IN (
                    SELECT id FROM Aufgaben WHERE kurs_id = :kid
                 )",
                ['kid' => $kursId]
            );

            $conn->executeStatement(
                "DELETE FROM Aufgaben WHERE kurs_id = :kid",
                ['kid' => $kursId]
            );
            $conn->executeStatement(
                "DELETE FROM Kurs_Schueler WHERE kurs_id = :kid",
                ['kid' => $kursId]
            );
            $conn->executeStatement(
                "DELETE FROM Kurs_Einstellungen WHERE kurs_id = :kid",
                ['kid' => $kursId]
            );
            $conn->executeStatement(
                "DELETE FROM Nachrichten WHERE kurs_id = :kid",
                ['kid' => $kursId]
            );
            $conn->executeStatement(
                "DELETE FROM Kurse WHERE id = :kid AND lehrer_id = :lid",
                ['kid' => $kursId, 'lid' => $lehrer->getId()]
            );

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * Kurs-Detail (Legacy: /faecher/{kursId})
     */
    #[Route('/kurse/{kursId<\\d+>}', name: 'course_detail', methods: ['GET'])]
    #[Route('/faecher/{kursId<\\d+>}', name: 'course_detail_legacy', methods: ['GET'])]
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

    /**
     * Kurs-Übersicht (Legacy: /faecher/{kursId}/uebersicht)
     */
    #[Route('/kurse/{kursId<\\d+>}/uebersicht', name: 'course_overview', methods: ['GET'])]
    #[Route('/faecher/{kursId<\\d+>}/uebersicht', name: 'course_overview_legacy', methods: ['GET'])]
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

    /**
     * Benotungsarten (Legacy: /faecher/{kursId}/benotungsarten)
     */
    #[Route('/kurse/{kursId<\\d+>}/benotungsarten', name: 'grading_types', methods: ['GET'])]
    #[Route('/faecher/{kursId<\\d+>}/benotungsarten', name: 'grading_types_legacy', methods: ['GET'])]
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

    /**
     * Schüler eines Kurses (Legacy: /faecher/{kursId}/schueler)
     */
    #[Route('/kurse/{kursId<\\d+>}/schueler', name: 'course_students', methods: ['GET'])]
    #[Route('/faecher/{kursId<\\d+>}/schueler', name: 'course_students_legacy', methods: ['GET'])]
    public function courseStudents(int $kursId, EntityManagerInterface $em): JsonResponse
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
                SELECT
                  b.schueler_id,
                  b.note
                FROM Benotung b
                INNER JOIN Kurse ku ON ku.fach_id = b.fach_id
                WHERE ku.id = :kid
                  AND b.lehrer_id = :lid

                UNION ALL

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
