<?php

namespace App\Controller;

use App\Entity\Lehrer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/teachers', name: 'api_teachers_')]
class TeacherController extends AbstractController
{
    /**
     * 🔹 GET /api/teachers/view
     * Holt alle Lehrer*innen aus der View "view_lehrer_uebersicht"
     */
    #[Route('/view', name: 'view', methods: ['GET'])]
    public function viewTeachers(EntityManagerInterface $em): JsonResponse
    {
        $conn = $em->getConnection();

        try {
            $sql = 'SELECT * FROM view_lehrer_uebersicht';
            $result = $conn->executeQuery($sql)->fetchAllAssociative();
            return $this->json($result);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Fehler beim Abrufen der Lehrer*innen',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔹 GET /api/teachers/{id}
     * Holt einen einzelnen Lehrer anhand der ID
     */
    #[Route('/{id<\d+>}', name: 'get', methods: ['GET'])]
    public function getTeacher(int $id, EntityManagerInterface $em): JsonResponse
    {
        $teacher = $em->getRepository(Lehrer::class)->find($id);

        if (!$teacher) {
            return new JsonResponse(['error' => 'Lehrer*in nicht gefunden'], 404);
        }

        return $this->json([
            'id' => $teacher->getId(),
            'vorname' => $teacher->getVorname(),
            'nachname' => $teacher->getNachname(),
            'email' => $teacher->getMicrosoft365User()?->getEmail(),
        ]);
    }

    /**
     * 🔹 DELETE /api/teachers/{id}
     * Löscht einen Lehrer anhand der ID
     */
    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function deleteTeacher(int $id, EntityManagerInterface $em): JsonResponse
    {
        $teacher = $em->getRepository(Lehrer::class)->find($id);

        if (!$teacher) {
            return new JsonResponse(['error' => 'Lehrer*in nicht gefunden'], 404);
        }

        try {
            $em->remove($teacher);
            $em->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Fehler beim Löschen des Lehrers',
                'details' => $e->getMessage(),
            ], 500);
        }

        return new JsonResponse(['message' => 'Lehrer*in erfolgreich gelöscht']);
    }
}