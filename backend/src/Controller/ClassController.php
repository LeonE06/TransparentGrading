<?php

namespace App\Controller;

use App\Entity\Klassen;
use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/classes')]
class ClassController extends AbstractController
{
    /**
     * 🔹 GET /api/classes
     * Gibt alle Klassen mit ihren Schülern zurück.
     */
    #[Route('', name: 'get_classes', methods: ['GET', 'OPTIONS'])]
    public function getAll(
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 204);
        }

        $classes = $em->getRepository(Klassen::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.schueler', 's')
            ->addSelect('s')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $json = $serializer->serialize($classes, 'json', [
            'groups' => ['class_read', 'student_read'],
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * 🔹 GET /api/classes/{id}
     * Gibt eine einzelne Klasse mit Schülern zurück.
     */
    #[Route('/{id}', name: 'get_class', methods: ['GET', 'OPTIONS'])]
    public function getOne(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 204);
        }

        $class = $em->getRepository(Klassen::class)->find($id);

        if (!$class) {
            return new JsonResponse(['error' => 'Klasse nicht gefunden.'], 404);
        }

        $json = $serializer->serialize($class, 'json', [
            'groups' => ['class_read', 'student_read'],
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * 🔹 POST /api/classes
     * Erstellt eine neue Klasse mit optionalen Schülern.
     */
    #[Route('', name: 'create_class', methods: ['POST', 'OPTIONS'])]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 204);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['name'])) {
            return new JsonResponse(['error' => 'Name der Klasse fehlt.'], 400);
        }

        $class = new Klassen();
        $class->setName($data['name']);

        // Schüler hinzufügen (bidirektional)
        $studentLog = [];
        if (!empty($data['students']) && is_array($data['students'])) {
            foreach ($data['students'] as $studentId) {
                $student = $em->getRepository(Schueler::class)->find($studentId);
                if ($student) {
                    $class->addSchueler($student);
                    $em->persist($student);
                    $studentLog[] = "Schüler-ID {$studentId} zugeordnet.";
                } else {
                    $studentLog[] = "Schüler-ID {$studentId} nicht gefunden!";
                }
            }
        }

        $em->persist($class);
        $em->flush();

        return new JsonResponse([
            'message' => 'Klasse erfolgreich erstellt!',
            'classId' => $class->getId(),
            'debug' => $studentLog,
        ], 201);
    }

    /**
     * 🔹 DELETE /api/classes/{id}
     * Löscht eine Klasse und setzt die Schüler-Beziehung zurück.
     * (Optional mit Cascade löschen – siehe Entity)
     */
    #[Route('/{id}', name: 'delete_class', methods: ['DELETE', 'OPTIONS'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 204);
        }

        $class = $em->getRepository(Klassen::class)->find($id);

        if (!$class) {
            return new JsonResponse(['error' => 'Klasse nicht gefunden.'], 404);
        }

        // Schüler-Beziehung lösen (nur wenn keine Cascade aktiv ist)
        foreach ($class->getSchueler() as $student) {
            $student->setKlasse(null);
            $em->persist($student);
        }

        $em->remove($class);
        $em->flush();

        return new JsonResponse(['message' => 'Klasse erfolgreich gelöscht.'], 200);
    }
}
