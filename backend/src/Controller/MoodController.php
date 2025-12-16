#[Route('/api/mood')]
class MoodController extends AbstractController
{
    #[Route('', name: 'api_mood_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof Schueler) {
            return $this->json(['error' => 'Nicht authentifiziert'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['mood'])) {
            return $this->json(['error' => 'Mood fehlt'], 400);
        }

        if (!in_array($data['mood'], ['gut', 'neutral', 'schlecht'])) {
            return $this->json(['error' => 'Ungültiger Mood-Wert'], 400);
        }

        $mood = new SchuelerMood();
        $mood->setMood($data['mood']);
        $mood->setSchueler($user);

        $em->persist($mood);
        $em->flush();

        return $this->json([
            'status' => 'ok',
            'message' => 'Mood gespeichert'
        ], 201);
    }
}
