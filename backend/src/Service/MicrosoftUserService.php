<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class MicrosoftUserService
{
    private Connection $conn;
    private string $frontendBase;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;

        // Umgebung automatisch erkennen
        $env = $_ENV['APP_ENV'] ?? 'prod';

        // Basis-URL fürs Frontend je nach Umgebung
        if ($env === 'dev') {
            // Lokale Entwicklungsumgebung (Docker + Vite)
            $this->frontendBase = 'http://localhost:5173';
        } else {
            // Produktionsumgebung (Vercel)
            $this->frontendBase = 'https://transparent-grading-flax.vercel.app';
        }
    }

    public function handleMicrosoftUser(string $vorname, string $nachname, string $email): string
    {
        // 👀 1️⃣ Prüfen, ob User im View existiert
        $existing = $this->conn->fetchAssociative(
            'SELECT * FROM view_ms365_user WHERE email = ?',
            [$email]
        );

        if (!$existing) {
            // 👤 2️⃣ Neuen Microsoft365-User in Basistabelle anlegen
            $this->conn->insert('tbl_Microsoft365_User', [
                'vorname' => $vorname,
                'nachname' => $nachname,
                'email' => $email,
                'lizenzen' => '',
                'proxyadressen' => '',
            ]);

            $userId = $this->conn->lastInsertId();

            // 🎓 3️⃣ Lehrer oder Schüler erkennen
            if (preg_match('/^[0-9]{4}@htl\.rennweg\.at$/', $email)) {
                // ➕ Schüler hinzufügen
                $this->conn->insert('Schueler', [
                    'ms365usr_id' => $userId,
                    'vorname' => $vorname,
                    'nachname' => $nachname,
                    'geburtsdatum' => null,
                    'klasse_id' => null,
                ]);
                return "{$this->frontendBase}/schueler/Klassenübersicht";
            } elseif (preg_match('/^[a-zA-Z]+@htl\.rennweg\.at$/', $email)) {
                // ➕ Lehrer hinzufügen
                $this->conn->insert('Lehrer', [
                    'ms365usr_id' => $userId,
                    'vorname' => $vorname,
                    'nachname' => $nachname,
                    'fach' => null,
                ]);
                return "{$this->frontendBase}/lehrer/Klassenübersicht";
            }
        } else {
            // 🔁 4️⃣ Benutzer existiert bereits → nur Redirect anhand Rolle
            if ($existing['rolle'] === 'Schueler') {
                return "{$this->frontendBase}/schueler/Klassenübersicht";
            } elseif ($existing['rolle'] === 'Lehrer') {
                return "{$this->frontendBase}/lehrer/Klassenübersicht";
            }
        }

        // 🧩 Fallback
        return "{$this->frontendBase}/";
    }
}
