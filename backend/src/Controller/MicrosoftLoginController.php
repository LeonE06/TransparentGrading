#[Route('/auth', name: 'auth_alias', methods: ['GET'])]
public function callback(Request $request): Response
{
    try {
        $code = $request->query->get('code');
        if (!$code) {
            return new Response('Kein Code erhalten', 400);
        }

        $tokenMicrosoft = $this->provider->getAccessToken('authorization_code', [
            'code'         => $code,
            'disableState' => true,
        ]);

        // --- Microsoft-User holen ---
        $graphUser = $this->provider->get('https://graph.microsoft.com/v1.0/me', $tokenMicrosoft);

        // -------------------------------
        // 💡 Lehrer + Schüler über Alias erkennen
        // -------------------------------
        $email = strtolower($graphUser['userPrincipalName'] ?? $graphUser['mail'] ?? '');
        $proxyAddresses = $graphUser['proxyAddresses'] ?? [];

        $studentEmail = null;
        $teacherEmail = null;

        foreach ($proxyAddresses as $address) {
            $address = strtolower(str_replace('smtp:', '', $address));
            $local = explode('@', $address)[0];

            // Schüler = 4 Zahlen
            if (preg_match('/^[0-9]{4}$/', $local)) {
                $studentEmail = $address;
            }

            // Lehrer = 3 Buchstaben (z.B. abc@htl...)
            if (preg_match('/^[a-z]{3}$/', $local)) {
                $teacherEmail = $address;
            }
        }

        if ($studentEmail) {
            $email = $studentEmail;
        } elseif ($teacherEmail) {
            $email = $teacherEmail;
        }

        $vorname  = $graphUser['givenName'] ?? '';
        $nachname = $graphUser['surname'] ?? '';

        // Benutzer speichern & Rolle ermitteln
        $role = $this->userService->handleMicrosoftUser($vorname, $nachname, $email);

        // JWT erstellen
        $payload = [
            'email' => $email,
            'role'  => $role,
            'exp'   => time() + 3600 // 1h gültig
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        // Weiter ins Frontend
        return $this->redirect($_ENV['FRONTEND_URL'] . '/auth/callback?token=' . $jwt);

    } catch (\Throwable $e) {
        return new Response('Fehler: ' . $e->getMessage(), 500);
    }
}
