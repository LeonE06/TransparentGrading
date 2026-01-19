<?php

namespace App\Controller;

use App\Entity\Aufgaben;
use App\Entity\AufgabenBewertung;
use App\Entity\Benotungsarten;
use App\Entity\Kurse;
use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer', name: 'api_lehrer_assessments_')]
class TeacherAssessmentsController extends AbstractController
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

    #[Route('/faecher/{kursId<\\d+>}/leistungsfeststellungen', name: 'list', methods: ['GET'])]
    public function listForCourse(int $kursId, Request $request, EntityManagerInterface $em): JsonResponse
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

        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'datum');
        $dir = strtolower((string) $request->query->get('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $allowedSort = [
            'thema' => 'thema',
            'typ' => 'typ',
            'datum' => 'datum',
            'gewichtung' => 'gewichtung',
        ];
        $orderBy = $allowedSort[$sort] ?? $allowedSort['datum'];

        $params = ['kid' => $kursId];
        $whereSearch = '';
        if ($search !== '') {
            $whereSearch = " AND (a.titel LIKE :q OR a.kommentar LIKE :q OR ba.name LIKE :q) ";
            $params['q'] = '%' . $search . '%';
        }

        $studentsTotal = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurs_Schueler WHERE kurs_id = :kid",
            ['kid' => $kursId]
        );

        $rows = $conn->fetchAllAssociative(
            "SELECT
                a.id AS id,
                COALESCE(NULLIF(a.kommentar, ''), a.titel) AS thema,
                ba.name AS typ,
                a.faelligkeit AS datum,
                a.gewichtung_prozent AS gewichtung_prozent,
                a.max_punkte AS max_punkte,
                AVG(ab.note) AS avg_note,
                COUNT(DISTINCT ab.schueler_id) AS participants
             FROM Aufgaben a
             LEFT JOIN Benotungsarten ba ON ba.id = a.benotungsart_id
             LEFT JOIN Aufgaben_Bewertung ab ON ab.aufgabe_id = a.id
             WHERE a.kurs_id = :kid
             {$whereSearch}
             GROUP BY a.id, thema, ba.name, a.faelligkeit, a.gewichtung_prozent, a.max_punkte
             ORDER BY {$orderBy} {$dir}",
            $params
        );

        $mapped = array_map(static function (array $r) use ($studentsTotal): array {
            $avgNote = $r['avg_note'] !== null ? (float) $r['avg_note'] : null;
            $avgPct = null;
            if ($avgNote !== null) {
                $avgPct = 100 - (($avgNote - 1) / 4) * 100;
                $avgPct = max(0, min(100, $avgPct));
            }

            $participation = null;
            if ($studentsTotal > 0) {
                $participation = ((int) $r['participants'] / $studentsTotal) * 100;
            }

            return [
                'id' => (int) $r['id'],
                'thema' => $r['thema'],
                'typ' => $r['typ'] ?? null,
                'datum' => $r['datum'],
                'gewichtungProzent' => $r['gewichtung_prozent'] !== null ? (float) $r['gewichtung_prozent'] : null,
                'maxPunkte' => $r['max_punkte'] !== null ? (int) $r['max_punkte'] : null,
                'klassenschnitt' => $avgNote !== null ? round($avgNote, 2) : null,
                'klassenschnittProzent' => $avgPct !== null ? round($avgPct, 1) : null,
                'teilnahmequote' => $participation !== null ? round($participation, 1) : null,
            ];
        }, $rows);

        return new JsonResponse($mapped);
    }

    #[Route('/faecher/{kursId<\\d+>}/leistungsfeststellungen', name: 'create', methods: ['POST'])]
    public function createForCourse(int $kursId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $kurs = $em->getRepository(Kurse::class)->find($kursId);
        if (!$kurs || $kurs->getLehrer()?->getId() !== $lehrer->getId()) {
            return new JsonResponse(['error' => 'Kurs nicht gefunden'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $thema = trim((string) ($data['thema'] ?? ''));
        $datum = (string) ($data['datum'] ?? '');
        $maxPunkte = $data['maxPunkte'] ?? null;
        $gewichtungProzent = $data['gewichtungProzent'] ?? null;
        $benotungsartId = $data['benotungsartId'] ?? null;

        if ($thema === '' || $datum === '') {
            return new JsonResponse(['error' => 'thema und datum sind Pflichtfelder'], 400);
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $datum);
        if (!$date) {
            return new JsonResponse(['error' => 'Ungültiges datum (YYYY-MM-DD)'], 400);
        }

        $benotungsart = null;
        if ($benotungsartId !== null && $benotungsartId !== '') {
            $benotungsart = $em->getRepository(Benotungsarten::class)->find((int) $benotungsartId);
        }

        $a = new Aufgaben();
        $a->setKurs($kurs);
        $a->setTitel($thema);
        $a->setBeschreibung($data['beschreibung'] ?? null);
        $a->setFaelligkeit($date);
        $a->setKommentar($thema);
        $a->setMaxPunkte($maxPunkte !== null ? (int) $maxPunkte : null);
        $a->setGewichtungProzent($gewichtungProzent !== null ? (float) $gewichtungProzent : null);
        $a->setBenotungsart($benotungsart);

        $em->persist($a);
        $em->flush();

        return new JsonResponse([
            'id' => $a->getId(),
        ], 201);
    }

    #[Route('/leistungsfeststellungen/{id<\\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(int $id, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $conn = $em->getConnection();
        $row = $conn->fetchAssociative(
            "SELECT
                a.id,
                a.kurs_id,
                COALESCE(NULLIF(a.kommentar, ''), a.titel) AS thema,
                a.faelligkeit AS datum,
                a.gewichtung_prozent,
                a.max_punkte,
                ba.id AS benotungsart_id,
                ba.name AS typ,
                k.name AS kurs_name,
                f.name AS fach_name,
                c.name AS klasse_name
             FROM Aufgaben a
             INNER JOIN Kurse k ON k.id = a.kurs_id
             INNER JOIN Faecher f ON f.id = k.fach_id
             LEFT JOIN Klassen c ON c.id = k.klasse_id
             LEFT JOIN Benotungsarten ba ON ba.id = a.benotungsart_id
             WHERE a.id = :aid AND k.lehrer_id = :lid",
            ['aid' => $id, 'lid' => $lehrer->getId()]
        );

        if (!$row) {
            return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
        }

        $studentsTotal = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurs_Schueler WHERE kurs_id = :kid",
            ['kid' => $row['kurs_id']]
        );

        $avgNote = $conn->fetchOne(
            "SELECT AVG(note) FROM Aufgaben_Bewertung WHERE aufgabe_id = :aid",
            ['aid' => $id]
        );
        $avgNote = $avgNote !== null ? (float) $avgNote : null;

        $avgPct = null;
        if ($avgNote !== null) {
            $avgPct = 100 - (($avgNote - 1) / 4) * 100;
            $avgPct = max(0, min(100, $avgPct));
        }

        $participants = (int) $conn->fetchOne(
            "SELECT COUNT(DISTINCT schueler_id) FROM Aufgaben_Bewertung WHERE aufgabe_id = :aid",
            ['aid' => $id]
        );

        $participation = null;
        if ($studentsTotal > 0) {
            $participation = ($participants / $studentsTotal) * 100;
        }

        $results = $conn->fetchAllAssociative(
            "SELECT
                ab.id,
                s.id AS schueler_id,
                s.vorname,
                s.nachname,
                ab.punkte,
                ab.note,
                COALESCE(ab.datum, a.faelligkeit) AS datum,
                ab.kommentar
             FROM Aufgaben_Bewertung ab
             INNER JOIN Aufgaben a ON a.id = ab.aufgabe_id
             INNER JOIN Schueler s ON s.id = ab.schueler_id
             WHERE ab.aufgabe_id = :aid
             ORDER BY s.nachname ASC, s.vorname ASC",
            ['aid' => $id]
        );

        $mappedResults = array_map(static function (array $r): array {
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
        }, $results);

        return new JsonResponse([
            'id' => (int) $row['id'],
            'kurs' => [
                'id' => (int) $row['kurs_id'],
                'name' => $row['kurs_name'],
                'fach' => $row['fach_name'],
                'klasse' => $row['klasse_name'],
            ],
            'thema' => $row['thema'],
            'typ' => $row['typ'] ?? null,
            'benotungsartId' => $row['benotungsart_id'] !== null ? (int) $row['benotungsart_id'] : null,
            'datum' => $row['datum'],
            'gewichtungProzent' => $row['gewichtung_prozent'] !== null ? (float) $row['gewichtung_prozent'] : null,
            'maxPunkte' => $row['max_punkte'] !== null ? (int) $row['max_punkte'] : null,
            'klassenschnitt' => $avgNote !== null ? round($avgNote, 2) : null,
            'klassenschnittProzent' => $avgPct !== null ? round($avgPct, 1) : null,
            'teilnahmequote' => $participation !== null ? round($participation, 1) : null,
            'schuelerleistungen' => $mappedResults,
        ]);
    }

    #[Route('/leistungsfeststellungen/{id<\\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $conn = $em->getConnection();
        $courseId = $conn->fetchOne(
            "SELECT a.kurs_id
             FROM Aufgaben a
             INNER JOIN Kurse k ON k.id = a.kurs_id
             WHERE a.id = :aid AND k.lehrer_id = :lid",
            ['aid' => $id, 'lid' => $lehrer->getId()]
        );
        if (!$courseId) {
            return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
        }

        $conn->executeStatement("DELETE FROM Aufgaben_Bewertung WHERE aufgabe_id = :aid", ['aid' => $id]);
        $conn->executeStatement("DELETE FROM Aufgaben WHERE id = :aid", ['aid' => $id]);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/leistungsfeststellungen/{id<\\d+>}/schuelerleistungen', name: 'add_student_result', methods: ['POST'])]
    public function addStudentResult(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        $conn = $em->getConnection();
        $row = $conn->fetchAssociative(
            "SELECT a.kurs_id
             FROM Aufgaben a
             INNER JOIN Kurse k ON k.id = a.kurs_id
             WHERE a.id = :aid AND k.lehrer_id = :lid",
            ['aid' => $id, 'lid' => $lehrer->getId()]
        );
        if (!$row) {
            return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $schuelerId = $data['schuelerId'] ?? null;
        if (!$schuelerId) {
            return new JsonResponse(['error' => 'schuelerId ist Pflicht'], 400);
        }

        $isInCourse = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM Kurs_Schueler WHERE kurs_id = :kid AND schueler_id = :sid",
            ['kid' => $row['kurs_id'], 'sid' => (int) $schuelerId]
        );
        if ($isInCourse === 0) {
            return new JsonResponse(['error' => 'Schüler ist nicht in diesem Kurs'], 400);
        }

        $note = $data['note'] ?? null;
        if ($note === null || $note === '') {
            return new JsonResponse(['error' => 'note ist Pflicht'], 400);
        }

        $punkte = $data['punkte'] ?? null;
        $datum = $data['datum'] ?? null;
        $date = null;
        if ($datum) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $datum);
            if (!$date) {
                return new JsonResponse(['error' => 'Ungültiges datum (YYYY-MM-DD)'], 400);
            }
        }

        $aufgabe = $em->getRepository(Aufgaben::class)->find($id);
        if (!$aufgabe) {
            return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
        }

        $schueler = $em->getRepository(Schueler::class)->find((int) $schuelerId);
        if (!$schueler) {
            return new JsonResponse(['error' => 'Schüler nicht gefunden'], 404);
        }

        $ab = new AufgabenBewertung();
        $ab->setAufgabe($aufgabe);
        $ab->setSchueler($schueler);
        $ab->setLehrer($lehrer);
        $ab->setNote((float) $note);
        $ab->setPunkte($punkte !== null && $punkte !== '' ? (int) $punkte : null);
        $ab->setDatum($date);
        $ab->setKommentar($data['kommentar'] ?? null);

        $em->persist($ab);
        $em->flush();

        return new JsonResponse(['id' => $ab->getId()], 201);
    }
}

