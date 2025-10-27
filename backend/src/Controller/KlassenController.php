<?php

namespace App\Controller;

use App\Entity\Klassen;
use App\Entity\Schueler;
use App\Repository\KlassenRepository;
use App\Repository\SchuelerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class KlassenController extends AbstractController
{
    #[Route('/klassen', methods: ['GET'])]
    public function getAll(KlassenRepository $repo): JsonResponse
    {
        $klassen = $repo->findAll();
        $data = array_map(fn($k) => [
            'id' => $k->getId(),
            'name' => $k->getName(),
            'anzahl_schueler' => count($k->getSchueler())
        ], $klassen);

        return $this->json($data);
    }

    #[Route('/klassen', methods: ['POST'])]
    public function create(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($req->getContent(), true);
        if (empty($data['name'])) {
            return $this->json(['error' => 'Klassenname fehlt'], 400);
        }

        $klasse = new Klassen();
        $klasse->setName($data['name']);
        $em->persist($klasse);
        $em->flush();

        return $this->json(['message' => 'Klasse erstellt', 'id' => $klasse->getId()]);
    }

    #[Route('/klassen/{id}/schueler', methods: ['GET'])]
    public function getSchueler(KlassenRepository $repo, int $id): JsonResponse
    {
        $klasse = $repo->find($id);
        if (!$klasse) return $this->json(['error' => 'Klasse nicht gefunden'], 404);

        $data = array_map(fn($s) => [
            'id' => $s->getId(),
            'vorname' => $s->getVorname(),
            'nachname' => $s->getNachname(),
        ], $klasse->getSchueler()->toArray());

        return $this->json($data);
    }

    #[Route('/klassen/{id}/schueler', methods: ['POST'])]
    public function addSchueler(
        int $id,
        Request $req,
        KlassenRepository $kRepo,
        SchuelerRepository $sRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($req->getContent(), true);
        if (empty($data['schueler_id'])) return $this->json(['error' => 'Schüler-ID fehlt'], 400);

        $klasse = $kRepo->find($id);
        $schueler = $sRepo->find($data['schueler_id']);

        if (!$klasse || !$schueler) return $this->json(['error' => 'Nicht gefunden'], 404);

        $schueler->setKlasse($klasse);
        $em->flush();

        return $this->json(['message' => 'Schüler hinzugefügt']);
    }

    #[Route('/schueler', methods: ['GET'])]
    public function searchSchueler(Request $req, SchuelerRepository $repo): JsonResponse
    {
        $search = $req->query->get('search', '');
        $qb = $repo->createQueryBuilder('s');

        if ($search) {
            $qb->where('s.vorname LIKE :q OR s.nachname LIKE :q')
               ->setParameter('q', "%$search%");
        }

        $schueler = $qb->setMaxResults(20)->getQuery()->getResult();

        $data = array_map(fn($s) => [
            'id' => $s->getId(),
            'vorname' => $s->getVorname(),
            'nachname' => $s->getNachname(),
        ], $schueler);

        return $this->json($data);
    }
}
