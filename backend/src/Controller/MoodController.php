<?php

namespace App\Controller;   // ✅ WICHTIG: Api entfernen

use App\Entity\Schueler;
use App\Entity\SchuelerMood;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mood')]
class MoodController extends AbstractController
{
    #[Route('', name: 'api_mood_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['schueler_id'], $data['mood'])) {
            return $this->json(['error' => 'Ungültige Daten'], 400);
        }

        if (!in_array($data['mood'], ['gut', 'neutral', 'schlecht'])) {
            return $this->json(['error' => 'Ungültiger Mood-Wert'], 400);
        }

        $schueler = $em->getRepository(Schueler::class)
            ->find($data['schueler_id']);

        if (!$schueler) {
            return $this->json(['error' => 'Schüler nicht gefunden'], 404);
        }

        $mood = new SchuelerMood();
        $mood->setMood($data['mood']);
        $mood->setSchueler($schueler);

        $em->persist($mood);
        $em->flush();

        return $this->json([
            'status' => 'ok',
            'message' => 'Mood gespeichert'
        ], 201);
    }

    #[Route('/{schuelerId}', name: 'api_mood_list', methods: ['GET'])]
    public function list(
        int $schuelerId,
        EntityManagerInterface $em
    ): JsonResponse {
        $conn = $em->getConnection();

        $data = $conn->fetchAllAssociative(
            'SELECT erstellt_am, mood
             FROM Schueler_Mood
             WHERE schueler_id = ?
             ORDER BY erstellt_am ASC',
            [$schuelerId]
        );

        return $this->json($data);
    }
}
