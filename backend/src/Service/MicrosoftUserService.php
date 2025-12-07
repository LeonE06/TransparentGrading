<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class MicrosoftUserService
{
    private Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function handleMicrosoftUser(string $vorname, string $nachname, string $email): string
    {
        // Benutzer im VIEW suchen
        $user = $this->conn->fetchAssociative(
            'SELECT * FROM view_ms365_user WHERE email = ?',
            [$email]
        );

        $role = $this->detectRole($email);

        if (!$user) {
            // Benutzer erstellen
            $this->conn->insert("tbl_Microsoft365_User", [
                'vorname' => $vorname,
                'nachname' => $nachname,
                'email' => $email,
                'lizenzen' => '',
                'proxyadressen' => '',
                'erstellungszeitpunkt' => (new \DateTime())->format("Y-m-d H:i:s")
            ]);

            $msId = (int)$this->conn->lastInsertId();

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
                    'ms365usr_id' => $msId
                ]);
            }
        }

        return $role;
    }

    public function detectRole(string $email): string
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
}
