<?php

namespace App\Controller;

use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/lehrer', name: 'api_lehrer_assessments_')]
class TeacherAssessmentsController extends AbstractController
{
    // ✅ In-Memory Speicher (RAM) – geht verloren bei Server-Restart
    private static array $mockAssessmentsByCourse = []; // [kursId => [assessment,...]]
    private static int $mockNextId = 1;

    private function resolveLehrer(EntityManagerInterface $em): ?Lehrer
    {
        $jwtUser = $this->getUser();
        if (!$jwtUser || !method_exists($jwtUser, 'getUserIdentifier')) return null;

        $email = $jwtUser->getUserIdentifier();
        $m365 = $em->getRepository(Microsoft365User::class)->findOneBy(['email' => $email]);
        if (!$m365) return null;

        return $em->getRepository(Lehrer::class)->findOneBy(['ms365User' => $m365]);
    }

    #[Route('/faecher/{kursId<\\d+>}/leistungsfeststellungen', name: 'list', methods: ['GET'])]
    public function listForCourse(int $kursId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) return new JsonResponse(['error' => 'Unauthenticated'], 401);

        $list = self::$mockAssessmentsByCourse[$kursId] ?? [];

        $search = trim((string) $request->query->get('search', ''));
        if ($search !== '') {
            $q = mb_strtolower($search);
            $list = array_values(array_filter($list, static function($a) use ($q) {
                $t = mb_strtolower((string)($a['thema'] ?? ''));
                return str_contains($t, $q);
            }));
        }

        // optional sort by datum desc
        usort($list, static function($a, $b) {
            return strcmp((string)($b['datum'] ?? ''), (string)($a['datum'] ?? ''));
        });

        return new JsonResponse($list);
    }

    #[Route('/faecher/{kursId<\\d+>}/leistungsfeststellungen', name: 'create', methods: ['POST'])]
    public function createForCourse(int $kursId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) return new JsonResponse(['error' => 'Unauthenticated'], 401);

        $data = json_decode($request->getContent(), true) ?: [];
        $thema = trim((string) ($data['thema'] ?? ''));
        $datum = (string) ($data['datum'] ?? '');

        if ($thema === '' || $datum === '') {
            return new JsonResponse(['error' => 'thema und datum sind Pflichtfelder'], 400);
        }

        // Datum minimal validieren (YYYY-MM-DD)
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $datum);
        if (!$date) {
            return new JsonResponse(['error' => 'Ungültiges datum (YYYY-MM-DD)'], 400);
        }

        $id = self::$mockNextId++;

        $assessment = [
            'id' => $id,
            'thema' => $thema,
            'typ' => null, // keine DB-lookup
            'benotungsartId' => ($data['benotungsartId'] ?? null) !== '' ? (int)($data['benotungsartId'] ?? 0) : null,
            'datum' => $datum,
            'gewichtungProzent' => ($data['gewichtungProzent'] ?? null) !== '' ? (float)($data['gewichtungProzent'] ?? 0) : null,
            'maxPunkte' => ($data['maxPunkte'] ?? null) !== '' ? (int)($data['maxPunkte'] ?? 0) : null,

            // KPIs bleiben leer (kein DB)
            'klassenschnitt' => null,
            'klassenschnittProzent' => null,
            'teilnahmequote' => null,
        ];

        if (!isset(self::$mockAssessmentsByCourse[$kursId])) {
            self::$mockAssessmentsByCourse[$kursId] = [];
        }
        self::$mockAssessmentsByCourse[$kursId][] = $assessment;

        return new JsonResponse(['id' => $id], 201);
    }

    #[Route('/leistungsfeststellungen/{id<\\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(int $id, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) return new JsonResponse(['error' => 'Unauthenticated'], 401);

        // Suche assessment in allen kursen
        foreach (self::$mockAssessmentsByCourse as $kursId => $list) {
            foreach ($list as $a) {
                if ((int)$a['id'] === (int)$id) {
                    return new JsonResponse([
                        'id' => (int)$a['id'],
                        'kurs' => [
                            'id' => (int)$kursId,
                            'name' => 'Mock Kurs',
                            'fach' => null,
                            'klasse' => null,
                        ],
                        'thema' => $a['thema'],
                        'typ' => $a['typ'],
                        'benotungsartId' => $a['benotungsartId'],
                        'datum' => $a['datum'],
                        'gewichtungProzent' => $a['gewichtungProzent'],
                        'maxPunkte' => $a['maxPunkte'],
                        'klassenschnitt' => null,
                        'klassenschnittProzent' => null,
                        'teilnahmequote' => null,
                        'schuelerleistungen' => [],
                    ]);
                }
            }
        }

        return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
    }

    #[Route('/leistungsfeststellungen/{id<\\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $lehrer = $this->resolveLehrer($em);
        if (!$lehrer) return new JsonResponse(['error' => 'Unauthenticated'], 401);

        foreach (self::$mockAssessmentsByCourse as $kursId => $list) {
            $new = array_values(array_filter($list, static fn($a) => (int)$a['id'] !== (int)$id));
            if (count($new) !== count($list)) {
                self::$mockAssessmentsByCourse[$kursId] = $new;
                return new JsonResponse(['status' => 'ok']);
            }
        }

        return new JsonResponse(['error' => 'Leistungsfeststellung nicht gefunden'], 404);
    }
}
