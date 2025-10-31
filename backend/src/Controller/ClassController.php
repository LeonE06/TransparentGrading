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
     * Gibt alle Klassen mit Schülern zurück.
     */
    #[Route('', name: 'get_classes', methods: ['GET'])]
    public function getAll(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $classes = $em->getRepository(Klassen::class)->findAll();

        // Schüler-Beziehungen serialisieren
        $json = $serializer->serialize($classes, 'json', [
            'groups' => ['class_read', 'student_read']
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * 🔹 POST /api/classes
     * Erstellt eine neue Klasse mit Schülern.
     */
    #[Route('', name: 'create_class', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['name'])) {
            return new JsonResponse(['error' => 'Name der Klasse fehlt.'], 400);
        }

        $class = new Klassen();
        $class->setName($data['name']);

        // Schüler IDs verknüpfen (optional)
        if (!empty($data['students']) && is_array($data['students'])) {
            foreach ($data['students'] as $studentId) {
                $student = $em->getRepository(Schueler::class)->find($studentId);
                if ($student) {
                    $student->setKlasse($class);
                    $em->persist($student);
                }
            }
        }

        $em->persist($class);
        $em->flush();

        return new JsonResponse(['message' => 'Klasse erfolgreich erstellt!'], 201);
    }

    /**
     * 🔹 GET /api/classes/{id}
     * Gibt eine einzelne Klasse mit Schülern zurück.
     */
    #[Route('/{id}', name: 'get_class', methods: ['GET'])]
    public function getOne(int $id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $class = $em->getRepository(Klassen::class)->find($id);

        if (!$class) {
            return new JsonResponse(['error' => 'Klasse nicht gefunden.'], 404);
        }

        $json = $serializer->serialize($class, 'json', [
            'groups' => ['class_read', 'student_read']
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * 🔹 DELETE /api/classes/{id}
     * Löscht eine Klasse und hebt Schülerverknüpfungen auf.
     */
    #[Route('/{id}', name: 'delete_class', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $class = $em->getRepository(Klassen::class)->find($id);

        if (!$class) {
            return new JsonResponse(['error' => 'Klasse nicht gefunden.'], 404);
        }

        // Schüler trennen, aber nicht löschen
        foreach ($class->getSchueler() as $student) {
            $student->setKlasse(null);
            $em->persist($student);
        }

        $em->remove($class);
        $em->flush();

        return new JsonResponse(['message' => 'Klasse erfolgreich gelöscht.'], 200);
    }
}
    