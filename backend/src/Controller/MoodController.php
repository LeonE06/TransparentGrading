<?php

namespace App\Controller;

use App\Entity\Mood;
use App\Entity\Schueler;
use App\Entity\Microsoft365User;
use App\Repository\MoodRepository;
use App\Repository\SchuelerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class MoodController extends AbstractController
{
    private function resolveSchueler(SchuelerRepository $schuelerRepo): ?Schueler
    {
        $user = $this->getUser();

        // Du loggst dich als Microsoft365User ein
        if ($user instanceof Microsoft365User) {
            // Nicht jeder MS365-User ist Schüler -> kann null sein
            return $schuelerRepo->findOneBy(['ms365User' => $user]);
        }

        // Fallback (falls du irgendwann Schueler als Security-User nutzt)
        if ($user instanceof Schueler) {
            return $user;
        }

        return null;
    }

    private function requireStudent(SchuelerRepository $schuelerRepo): Schueler|JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $schueler = $this->resolveSchueler($schuelerRepo);

        if (!$schueler) {
            return $this->json(['message' => 'Forbidden (only students)'], 403);
        }

        return $schueler;
    }

    #[Route('/api/mood', name: 'mood_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SchuelerRepository $schuelerRepo
    ): JsonResponse {
        $schuelerOrResponse = $this->requireStudent($schuelerRepo);
        if ($schuelerOrResponse instanceof JsonResponse) {
            return $schuelerOrResponse;
        }
        $schueler = $schuelerOrResponse;

        $data = json_decode($request->getContent(), true) ?? [];

        $moodValue = $data['mood'] ?? null;
        $note = $data['note'] ?? null;

        $allowed = ['gut', 'neutral', 'schlecht'];
        if (!$moodValue || !in_array($moodValue, $allowed, true)) {
            return $this->json(['message' => 'Invalid mood'], 400);
        }

        $mood = (new Mood())
            ->setSchueler($schueler)
            ->setMood($moodValue)
            ->setNote($note);

        $em->persist($mood);
        $em->flush();

        return $this->json([
            'message' => 'saved',
            'id' => $mood->getId(),
            'createdAt' => $mood->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 201);
    }

    #[Route('/api/mood', name: 'mood_list', methods: ['GET'])]
    public function list(
        MoodRepository $repo,
        SerializerInterface $serializer,
        SchuelerRepository $schuelerRepo
    ): JsonResponse {
        $schuelerOrResponse = $this->requireStudent($schuelerRepo);
        if ($schuelerOrResponse instanceof JsonResponse) {
            return $schuelerOrResponse;
        }
        $schueler = $schuelerOrResponse;

        $items = $repo->findLatestBySchueler($schueler, 60);

        $json = $serializer->serialize($items, 'json', ['groups' => ['mood:read']]);
        return new JsonResponse($json, 200, [], true);
    }
}
