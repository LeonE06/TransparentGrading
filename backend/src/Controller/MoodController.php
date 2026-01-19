<?php

namespace App\Controller;

use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use App\Entity\Mood;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mood')]
class MoodController extends AbstractController
{
    #[Route('', name: 'api_mood_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // ✅ wie /api/schueler/me: eingeloggten User -> MS365 User -> Schueler
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Nicht authentifiziert'], 401);
        }

        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        $schueler = $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);

        if (!$schueler) {
            return $this->json(['error' => 'Schüler nicht gefunden'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['mood'])) {
            return $this->json(['error' => 'Mood fehlt'], 400);
        }

        if (!in_array($data['mood'], ['gut', 'neutral', 'schlecht'], true)) {
            return $this->json(['error' => 'Ungültiger Mood-Wert'], 400);
        }

        $mood = new Mood();
        $mood->setMood($data['mood']);
        $mood->setSchueler($schueler);

        // optional: note speichern (falls du es im Frontend hast)
        if (isset($data['note'])) {
            $mood->setNote($data['note']);
        }

        $em->persist($mood);
        $em->flush();

        return $this->json(['status' => 'ok', 'message' => 'Mood gespeichert'], 201);
    }

    #[Route('/me', name: 'api_mood_me', methods: ['GET'])]
    public function listMyMoods(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Nicht authentifiziert'], 401);
        }

        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        $schueler = $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);

        if (!$schueler) {
            return $this->json(['error' => 'Schüler nicht gefunden'], 404);
        }

        // ✅ ohne SQL: direkt Doctrine
        $moods = $em->getRepository(Mood::class)->findBy(
            ['schueler' => $schueler],
            ['createdAt' => 'ASC']
        );

        // einfache JSON Ausgabe
        $out = array_map(fn(Mood $m) => [
            'created_at' => $m->getCreatedAt()?->format('Y-m-d H:i:s'),
            'mood' => $m->getMood(),
            'note' => $m->getNote(),
        ], $moods);

        return $this->json($out);
    }


    #[Route('/stats', name: 'api_mood_stats', methods: ['GET'])]
    public function stats(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Nicht authentifiziert'], 401);
        }

        $ms365User = $em->getRepository(\App\Entity\Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);
        $schueler = $em->getRepository(\App\Entity\Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);

        if (!$schueler) {
            return $this->json(['error' => 'Schüler nicht gefunden'], 404);
        }

        $conn = $em->getConnection();

        $rows = $conn->fetchAllAssociative(
            "SELECT day, avg_score, entries
         FROM mood_daily
         WHERE schueler_id = ?
         ORDER BY day ASC",
            [$schueler->getId()]
        );

        return $this->json($rows);
    }
        #[Route('', name: 'api_mood_delete', methods: ['DELETE'])]
    public function deleteMyMoods(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Nicht authentifiziert'], 401);
        }

        $ms365User = $em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        $schueler = $em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);

        if (!$schueler) {
            return $this->json(['error' => 'Schüler nicht gefunden'], 404);
        }

        // ✅ Nur die Mood-Daten vom eingeloggten Schüler löschen
        $conn = $em->getConnection();
        $deleted = $conn->executeStatement(
            'DELETE FROM mood WHERE schueler_id = ?',
            [$schueler->getId()]
        );

        return $this->json([
            'status' => 'ok',
            'deleted' => $deleted
        ], 200);
    }

    #[Route('/today', name: 'api_mood_today', methods: ['GET'])]
    public function hasMoodToday(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user)
            return $this->json(['error' => 'Nicht authentifiziert'], 401);

        $ms365User = $em->getRepository(\App\Entity\Microsoft365User::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);
        $schueler = $em->getRepository(\App\Entity\Schueler::class)
            ->findOneBy(['ms365User' => $ms365User]);

        if (!$schueler)
            return $this->json(['error' => 'Schüler nicht gefunden'], 404);

        $conn = $em->getConnection();
        $exists = $conn->fetchOne(
            "SELECT 1 FROM mood WHERE schueler_id = ? AND DATE(created_at) = CURDATE() LIMIT 1",
            [$schueler->getId()]
        );

        return $this->json(['hasMoodToday' => (bool) $exists]);
    }
}