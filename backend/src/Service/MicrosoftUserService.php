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

        $env = $_ENV['APP_ENV'] ?? 'prod';
        $this->frontendBase = $env === 'dev'
            ? 'http://localhost:5173'
            : 'https://transparent-grading-flax.vercel.app';
    }

    public function handleMicrosoftUser(string $vorname, string $nachname, string $email): string
    {
        // 1️⃣ Im View nachschauen
        $user = $this->conn->fetchAssociative(
            "SELECT * FROM view_ms365_user WHERE email = ?",
            [$email]
        );

        // 2️⃣ Falls Benutzer im View existiert → nur Redirect
        if ($user) {
            return $this->redirectByRole($user['rolle']);
        }

        // 3️⃣ Benutzer in tbl_Microsoft365_User anlegen
        $this->conn->insert("tbl_Microsoft365_User", [
            'vorname' => $vorname,
            'nachname' => $nachname,
            'email' => $email,
            'lizenzen' => '',
            'proxyadressen' => '',
            'erstellungszeitpunkt' => (new \DateTime())->format("Y-m-d H:i:s")
        ]);

        $msId = (int)$this->conn->lastInsertId();

        // 4️⃣ Rolle anhand der Email bestimmen
        $role = $this->detectRole($email);

        // 5️⃣ Abhängig von Rolle: Lehrer/Schüler Datensatz erzeugen
        if ($role === 'Schueler') {
            $this->conn->insert("Schueler", [
                'vorname' => $vorname,
                'nachname' => $nachname,
                'geburtsdatum' => null,
                'klasse_id' => null,
                'ms365usr_id' => $msId
            ]);
        }

        if ($role === 'Lehrer') {
            $this->conn->insert("Lehrer", [
                'vorname' => $vorname,
                'nachname' => $nachname,
                'fach' => null,
                'MS365Usr_ID' => $msId
            ]);
        }

        return $this->redirectByRole($role);
    }

    private function detectRole(string $email): string
    {
        $local = explode('@', $email)[0];

        if (preg_match('/^[0-9]{4}$/', $local)) {
            return 'Schueler';
        }

        if (preg_match('/^[A-Za-z]{3}$/', $local)) {
            return 'Lehrer';
        }

        return 'Unbekannt';
    }

    private function redirectByRole(string $role): string
    {
        return match ($role) {
            'Schueler' => "{$this->frontendBase}/schueler/Klassenübersicht",
            'Lehrer' => "{$this->frontendBase}/lehrer/Klassenübersicht",
            default => "{$this->frontendBase}/",
        };
    }
}
