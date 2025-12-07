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
        // Prüfen ob Benutzer schon existiert
        $user = $this->conn->fetchAssociative(
            'SELECT * FROM tbl_Microsoft365_User WHERE email = ?',
            [$email]
        );

        if (!$user) {
            // Benutzer in tbl_Microsoft365_User anlegen
            $this->conn->insert("tbl_Microsoft365_User", [
                'vorname' => $vorname,
                'nachname' => $nachname,
                'email' => $email,
                'lizenzen' => '',
                'proxyadressen' => '',
                'erstellungszeitpunkt' => (new \DateTime())->format("Y-m-d H:i:s")
            ]);

            $msId = (int)$this->conn->lastInsertId();

            // Rolle anhand Domain festlegen
            $role = $this->guessRoleFromEmail($email);

            if ($role === 'Schueler') {
                $this->conn->insert("Schueler", [
                    'ms365usr_id' => $msId,
                    'vorname' => $vorname,
                    'nachname' => $nachname,
                    'geburtsdatum' => null,
                    'klasse_id' => null
                ]);
            } elseif ($role === 'Lehrer') {
                $this->conn->insert("Lehrer", [
                    'ms365usr_id' => $msId,
                    'vorname' => $vorname,
                    'nachname' => $nachname,
                    'fach' => null
                ]);
            }

            return $role;
        }

        // Benutzer existiert → Rolle aus DB bestimmen
        return $this->getRoleFromDB($email);
    }


    private function guessRoleFromEmail(string $email): string
    {
        $local = explode('@', $email)[0];

        // Schüler: nur Zahlen (Schulkennung)
        if (preg_match('/^[0-9]{4}$/', $local)) {
            return 'Schueler';
        }

        // Lehrer: Schul-Domain
        if (str_ends_with($email, '@htl.rennweg.at')) {
            return 'Lehrer';
        }

        return 'Unbekannt';
    }


    private function getRoleFromDB(string $email): string
    {
        $role = $this->conn->fetchOne(
            "SELECT 'Schueler' FROM Schueler s JOIN tbl_Microsoft365_User u ON s.ms365usr_id = u.id WHERE u.email = ?",
            [$email]
        );

        if ($role) return 'Schueler';

        $role = $this->conn->fetchOne(
            "SELECT 'Lehrer' FROM Lehrer l JOIN tbl_Microsoft365_User u ON l.ms365usr_id = u.id WHERE u.email = ?",
            [$email]
        );

        if ($role) return 'Lehrer';

        return 'Unbekannt';
    }
}
