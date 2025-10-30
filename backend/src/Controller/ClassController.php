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
    #[Route('', methods: ['GET'])]
    public function getAll(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $classes = $em->getRepository(Klassen::class)->findAll();
        $json = $serializer->serialize($classes, 'json', ['groups' => ['class_read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $klasse = new Klassen();
        $klasse->setName($data['name'] ?? 'Unbenannt');

        // Schüler hinzufügen
        if (isset($data['students'])) {
            foreach ($data['students'] as $studentId) {
                $schueler = $em->getRepository(Schueler::class)->find($studentId);
                if ($schueler) {
                    $klasse->addSchueler($schueler);
                }
            }
        }

        $em->persist($klasse);
        $em->flush();

        return $this->json(['message' => 'Klasse erfolgreich erstellt'], 201);
    }
}
