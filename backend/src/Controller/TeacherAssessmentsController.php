<?php

namespace App\Controller;

use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\ParentNotificationService;

#[Route('/api/lehrer', name: 'api_lehrer_assessments_')]
class TeacherAssessmentsController extends AbstractController
{
    private function createNotification(Connection $conn, ?int $kursId, string $titel, string $inhalt, array $schuelerIds, ?int $zielSchuelerId = null): void
    {
        if ($schuelerIds === []) {
            return;
        }

        $conn->insert('Nachrichten', [
            'kurs_id' => $kursId,
            'ziel_schueler_id' => $zielSchuelerId,
            'titel' => $titel,
            'inhalt' => $inhalt,
            'erstellt_am' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $nachrichtId = (int) $conn->lastInsertId();

        foreach ($schuelerIds as $sid) {
            $conn->insert('Nachrichten_Status', [
                'nachricht_id' => $nachrichtId,
                'schueler_id' => (int) $sid,
                'gelesen' => 0,
            ]);
        }
    }

    private function calculateNotePercentage(float $note): float
    {
        $percentage = 100 - (($note - 1) / 4) * 100;

        return max(0, min(100, $percentage));
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

    private function assertCourseOwnedByTeacher(Connection $conn, int $kursId, int $lehrerId): bool
    {
        return (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurse WHERE id = :kid AND lehrer_id = :lid",
            ['kid' => $kursId, 'lid' => $lehrerId]
        ) > 0;
    }

    private function getKursIdForAufgabeIfOwned(Connection $conn, int $aufgabeId, int $lehrerId): ?int
    {
        $kursId = $conn->fetchOne(
            "SELECT a.kurs_id
             FROM Aufgaben a
             INNER JOIN Kurse k ON k.id = a.kurs_id
             WHERE a.id = :aid AND k.lehrer_id = :lid",
            ['aid' => $aufgabeId, 'lid' => $lehrerId]
        );

        return $kursId !== false ? (int) $kursId : null;
    }

    // ------------------------------------------------------------
    // GET: Liste Leistungsfeststellungen pro Kurs (Aufgaben)
    // ------------------------------------------------------------
    #[Route('/faecher/{kursId<\\d+>}/leistungsfeststellungen', name: 'list', methods: ['GET'])]
    public function listForCourse(int $kursId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer)
            return new JsonResponse(['error' => 'Unauthenticated'], 401);

        $conn = $em->getConnection();

        if (!$this->assertCourseOwnedByTeacher($conn, $kursId, $lehrer->getId())) {
            return new JsonResponse(['error' => 'Kurs nicht gefunden'], 404);
        }

        $search = trim((string) $request->query->get('search', ''));

        $sql = "
            SELECT
                a.id,
                COALESCE(a.kommentar, a.titel) AS thema,
                ba.name AS typ,
                a.benotungsart_id AS benotungsartId,
                a.faelligkeit AS datum,
                a.gewichtung_prozent AS gewichtungProzent,
                a.max_punkte AS maxPunkte,
                ROUND(AVG(ab.note), 2) AS klassenschnitt,
                COUNT(DISTINCT ab.schueler_id) AS teilnehmer
            FROM Aufgaben a
            LEFT JOIN Benotungsarten ba ON ba.id = a.benotungsart_id
            LEFT JOIN Aufgaben_Bewertung ab ON ab.aufgabe_id = a.id
            WHERE a.kurs_id = :kid
        ";
        $params = ['kid' => $kursId];

        if ($search !== '') {
            $sql .= " AND LOWER(COALESCE(a.kommentar, a.titel)) LIKE :q ";
            $params['q'] = '%' . mb_strtolower($search) . '%';
        }

        $sql .= "
            GROUP BY a.id, thema, typ, benotungsartId, datum, gewichtungProzent, maxPunkte
            ORDER BY a.faelligkeit DESC, a.id DESC
        ";

        $rows = $conn->fetchAllAssociative($sql, $params);

        $studentsTotal = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurs_Schueler WHERE kurs_id = :kid",
            ['kid' => $kursId]
        );

        $mapped = array_map(static function (array $r) use ($studentsTotal): array {
            $teilnahmequote = null;
            if ($studentsTotal > 0) {
                $teilnahmequote = round(((int) $r['teilnehmer'] / $studentsTotal) * 100, 1);
            }

            return [
                'id' => (int) $r['id'],
                'thema' => $r['thema'],
                'typ' => $r['typ'],
                'benotungsartId' => $r['benotungsartId'] !== null ? (int) $r['benotungsartId'] : null,
                'datum' => $r['datum'],
                'gewichtungProzent' => $r['gewichtungProzent'] !== null ? (float) $r['gewichtungProzent'] : null,
                'maxPunkte' => $r['maxPunkte'] !== null ? (int) $r['maxPunkte'] : null,
                'klassenschnitt' => $r['klassenschnitt'] !== null ? (float) $r['klassenschnitt'] : null,
                'klassenschnittProzent' => null,
                'teilnahmequote' => $teilnahmequote,
            ];
        }, $rows);

        return new JsonResponse($mapped);
    }

    // ------------------------------------------------------------
    // POST: Leistungsfeststellung erstellen (INSERT Aufgaben)
    // ------------------------------------------------------------
    #[Route('/faecher/{kursId<\\d+>}/leistungsfeststellungen', name: 'create', methods: ['POST'])]
    public function createForCourse(int $kursId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer)
            return new JsonResponse(['error' => 'Unauthenticated'], 401);

        $conn = $em->getConnection();

        if (!$this->assertCourseOwnedByTeacher($conn, $kursId, $lehrer->getId())) {
            return new JsonResponse(['error' => 'Kurs nicht gefunden'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];

        $thema = trim((string) ($data['thema'] ?? ''));
        $datum = (string) ($data['datum'] ?? '');

        if ($thema === '' || $datum === '') {
            return new JsonResponse(['error' => 'thema und datum sind Pflichtfelder'], 400);
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $datum);
        if (!$date) {
            return new JsonResponse(['error' => 'Ungültiges datum (YYYY-MM-DD)'], 400);
        }

        $benotungsartId = $data['benotungsartId'] ?? null;
        $benotungsartId = ($benotungsartId === '' || $benotungsartId === null) ? null : (int) $benotungsartId;

        $defaultGewichtung = null;
        if ($benotungsartId !== null) {
            $typeRow = $conn->fetchAssociative(
                "SELECT id, gewichtung FROM Benotungsarten WHERE id = :id",
                ['id' => $benotungsartId]
            );
            if ($typeRow === false) {
                return new JsonResponse(['error' => 'Ungültige benotungsartId'], 400);
            }
            $defaultGewichtung = $typeRow['gewichtung'] !== null ? (float) $typeRow['gewichtung'] : null;
        }

        $gewichtung = $data['gewichtungProzent'] ?? null;
        $gewichtung = ($gewichtung === '' || $gewichtung === null) ? null : (float) $gewichtung;
        if ($gewichtung === null) {
            $gewichtung = $defaultGewichtung;
        }
        if ($gewichtung !== null && ($gewichtung < 0 || $gewichtung > 100)) {
            return new JsonResponse(['error' => 'gewichtungProzent muss zwischen 0 und 100 liegen'], 400);
        }

        $maxPunkte = $data['maxPunkte'] ?? null;
        $maxPunkte = ($maxPunkte === '' || $maxPunkte === null) ? null : (int) $maxPunkte;
        if ($maxPunkte !== null && $maxPunkte < 0) {
            return new JsonResponse(['error' => 'maxPunkte darf nicht negativ sein'], 400);
        }

        $conn->insert('Aufgaben', [
            'kurs_id' => $kursId,
            'titel' => $thema,
            'beschreibung' => null,
            'faelligkeit' => $datum,
            'kommentar' => $thema,
            'max_punkte' => $maxPunkte,
            'gewichtung_prozent' => $gewichtung,
            'benotungsart_id' => $benotungsartId,
        ]);

        $newId = (int) $conn->lastInsertId();

        // Kursname holen
        $kursName = (string) $conn->fetchOne(
            'SELECT name FROM Kurse WHERE id = :id',
            ['id' => $kursId]
        );

        // Text der Benachrichtigung
        $titel = 'Neue Leistungsfeststellung';
        $inhalt = 'Im Kurs "' . $kursName . '" wurde eine neue Leistungsfeststellung angelegt (Datum: ' . $datum . ').';

        $schuelerIds = $conn->fetchFirstColumn(
            'SELECT schueler_id FROM Kurs_Schueler WHERE kurs_id = :kid',
            ['kid' => $kursId]
        );

        $this->createNotification($conn, $kursId, $titel, $inhalt, $schuelerIds);

        return new JsonResponse(['id' => $newId], 201);
    }

    // ------------------------------------------------------------
    // GET: Detail Leistungsfeststellung + Schülerleistungen
    // ------------------------------------------------------------
    #[Route('/leistungsfeststellungen/{id<\\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(int $id, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer)
            return new JsonResponse(['error' => 'Unauthenticated'], 401);

        $conn = $em->getConnection();

        $kursId = $this->getKursIdForAufgabeIfOwned($conn, $id, $lehrer->getId());
        if ($kursId === null) {
            return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
        }

        $a = $conn->fetchAssociative(
            "SELECT
                a.id,
                a.kurs_id,
                a.titel,
                a.kommentar,
                a.faelligkeit AS datum,
                a.max_punkte AS maxPunkte,
                a.gewichtung_prozent AS gewichtungProzent,
                a.benotungsart_id AS benotungsartId,
                ba.name AS typ,
                k.name AS kurs_name,
                f.name AS fach_name,
                c.name AS klasse_name
             FROM Aufgaben a
             INNER JOIN Kurse k ON k.id = a.kurs_id
             INNER JOIN Faecher f ON f.id = k.fach_id
             LEFT JOIN Klassen c ON c.id = k.klasse_id
             LEFT JOIN Benotungsarten ba ON ba.id = a.benotungsart_id
             WHERE a.id = :id",
            ['id' => $id]
        );

        if (!$a)
            return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);

        $rows = $conn->fetchAllAssociative(
            "SELECT
                ab.id,
                ab.schueler_id,
                s.vorname,
                s.nachname,
                ab.punkte,
                ab.note,
                ab.datum,
                ab.kommentar
             FROM Aufgaben_Bewertung ab
             INNER JOIN Schueler s ON s.id = ab.schueler_id
             WHERE ab.aufgabe_id = :aid
             ORDER BY s.nachname ASC, s.vorname ASC",
            ['aid' => $id]
        );

        $schuelerleistungen = array_map(static function (array $r): array {
            return [
                'id' => (int) $r['id'],
                'schuelerId' => (int) $r['schueler_id'],
                'vorname' => $r['vorname'],
                'nachname' => $r['nachname'],
                'punkte' => $r['punkte'] !== null ? (int) $r['punkte'] : null,
                'note' => $r['note'] !== null ? (float) $r['note'] : null,
                'datum' => $r['datum'],
                'kommentar' => $r['kommentar'],
            ];
        }, $rows);

        $klassenschnitt = null;
        $klassenschnittProzent = null;
        if (\count($rows) > 0) {
            $noten = array_values(array_filter(array_map(
                static fn(array $row): ?float => $row['note'] !== null ? (float) $row['note'] : null,
                $rows
            ), static fn(?float $value): bool => $value !== null));

            if (\count($noten) > 0) {
                $klassenschnitt = round(array_sum($noten) / \count($noten), 2);
                $klassenschnittProzent = round($this->calculateNotePercentage($klassenschnitt), 1);
            }

            if ($a['maxPunkte'] !== null) {
                $punkte = array_values(array_filter(array_map(
                    static fn(array $row): ?float => $row['punkte'] !== null ? (float) $row['punkte'] : null,
                    $rows
                ), static fn(?float $value): bool => $value !== null));

                if (\count($punkte) > 0 && (float) $a['maxPunkte'] > 0) {
                    $klassenschnittProzent = round((array_sum($punkte) / \count($punkte)) / (float) $a['maxPunkte'] * 100, 1);
                }
            }
        }

        $studentsTotal = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurs_Schueler WHERE kurs_id = :kid",
            ['kid' => $kursId]
        );
        $teilnahmequote = $studentsTotal > 0
            ? round((\count($schuelerleistungen) / $studentsTotal) * 100, 1)
            : null;

        return new JsonResponse([
            'id' => (int) $a['id'],
            'kurs' => [
                'id' => (int) $a['kurs_id'],
                'name' => $a['kurs_name'],
                'fach' => $a['fach_name'],
                'klasse' => $a['klasse_name'],
            ],
            'thema' => $a['kommentar'] ?? $a['titel'],
            'typ' => $a['typ'],
            'benotungsartId' => $a['benotungsartId'] !== null ? (int) $a['benotungsartId'] : null,
            'datum' => $a['datum'],
            'gewichtungProzent' => $a['gewichtungProzent'] !== null ? (float) $a['gewichtungProzent'] : null,
            'maxPunkte' => $a['maxPunkte'] !== null ? (int) $a['maxPunkte'] : null,
            'klassenschnitt' => $klassenschnitt,
            'klassenschnittProzent' => $klassenschnittProzent,
            'teilnahmequote' => $teilnahmequote,
            'schuelerleistungen' => $schuelerleistungen,
        ]);
    }

    // ------------------------------------------------------------
    // DELETE: Löschen (Bewertungen + Aufgabe)
    // ------------------------------------------------------------
    #[Route('/leistungsfeststellungen/{id<\\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer)
            return new JsonResponse(['error' => 'Unauthenticated'], 401);

        $conn = $em->getConnection();

        $kursId = $this->getKursIdForAufgabeIfOwned($conn, $id, $lehrer->getId());
        if ($kursId === null) {
            return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
        }

        $conn->executeStatement("DELETE FROM Aufgaben_Bewertung WHERE aufgabe_id = :aid", ['aid' => $id]);
        $conn->executeStatement("DELETE FROM Aufgaben WHERE id = :aid", ['aid' => $id]);

        return new JsonResponse(['status' => 'ok']);
    }

    // ------------------------------------------------------------
    // POST: Schülerleistung speichern (INSERT Aufgaben_Bewertung)
    // ------------------------------------------------------------
    #[Route('/leistungsfeststellungen/{id<\\d+>}/schuelerleistungen', name: 'create_student_result', methods: ['POST'])]
    public function createStudentResult(int $id, Request $request, EntityManagerInterface $em, ParentNotificationService $parentNotificationService): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer)
            return new JsonResponse(['error' => 'Unauthenticated'], 401);

        $conn = $em->getConnection();

        $kursId = $this->getKursIdForAufgabeIfOwned($conn, $id, $lehrer->getId());
        if ($kursId === null) {
            return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];

        $schuelerId = $data['schuelerId'] ?? $data['schueler_id'] ?? null;
        $schuelerId = ($schuelerId === '' || $schuelerId === null) ? null : (int) $schuelerId;
        if (!$schuelerId) {
            return new JsonResponse(['error' => 'schuelerId ist Pflicht'], 400);
        }

        // Schüler ist im Kurs?
        $inCourse = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurs_Schueler WHERE kurs_id = :kid AND schueler_id = :sid",
            ['kid' => $kursId, 'sid' => $schuelerId]
        );
        if ($inCourse === 0) {
            return new JsonResponse(['error' => 'Schüler ist nicht in diesem Kurs'], 400);
        }

        $punkte = $data['punkte'] ?? null;
        $punkte = ($punkte === '' || $punkte === null) ? null : (int) $punkte;

        $note = $data['note'] ?? null;
        $note = ($note === '' || $note === null) ? null : (float) $note;

        // datum: du hast DATETIME mit Default current_timestamp()
        $datum = (string) ($data['datum'] ?? '');
        if ($datum !== '') {
            // akzeptiere "YYYY-MM-DD" -> mache "YYYY-MM-DD 00:00:00"
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $datum);
            if (!$d)
                return new JsonResponse(['error' => 'Ungültiges datum (YYYY-MM-DD)'], 400);
            $datum = $d->format('Y-m-d 00:00:00');
        } else {
            $datum = null; // DB default nimmt current_timestamp()
        }

        $kommentar = $data['kommentar'] ?? null;
        $existingRow = $conn->fetchAssociative(
            "SELECT id, datum
             FROM Aufgaben_Bewertung
             WHERE aufgabe_id = :aid AND schueler_id = :sid
             LIMIT 1",
            ['aid' => $id, 'sid' => $schuelerId]
        );

        $isNewStudentResult = $existingRow === false;

        if (!$isNewStudentResult) {
            $conn->update('Aufgaben_Bewertung', [
                'lehrer_id' => $lehrer->getId(),
                'punkte' => $punkte,
                'note' => $note,
                'datum' => $datum ?? $existingRow['datum'],
                'kommentar' => $kommentar,
            ], [
                'id' => (int) $existingRow['id'],
            ]);
        } else {
            $conn->insert('Aufgaben_Bewertung', [
                'aufgabe_id' => $id,
                'schueler_id' => $schuelerId,
                'lehrer_id' => $lehrer->getId(),
                'punkte' => $punkte,
                'note' => $note,
                'datum' => $datum,
                'kommentar' => $kommentar,
            ]);
        }

        if ($isNewStudentResult) {
            $assessment = $conn->fetchAssociative(
                "SELECT COALESCE(a.kommentar, a.titel) AS thema, a.faelligkeit, k.name AS kurs_name
                 FROM Aufgaben a
                 INNER JOIN Kurse k ON k.id = a.kurs_id
                 WHERE a.id = :aid",
                ['aid' => $id]
            );

            $titel = 'Neue Schülerleistung';
            $inhalt = 'Für die Leistungsfeststellung "' . ((string) ($assessment['thema'] ?? '')) . '" im Kurs "' . ((string) ($assessment['kurs_name'] ?? '')) . '" wurde eine neue Bewertung eingetragen.';

            if (!empty($assessment['faelligkeit'])) {
                $inhalt .= ' Termin: ' . (string) $assessment['faelligkeit'] . '.';
            }

            if ($note !== null) {
                $inhalt .= ' Note: ' . rtrim(rtrim(number_format($note, 2, '.', ''), '0'), '.') . '.';
            } elseif ($punkte !== null) {
                $inhalt .= ' Punkte: ' . $punkte . '.';
            }

            $this->createNotification($conn, $kursId, $titel, $inhalt, [$schuelerId], $schuelerId);
        }

        $parentNotificationService
            ->checkAndNotify($schuelerId, $kursId);

        return new JsonResponse(['status' => 'ok'], 201);
    }
}
