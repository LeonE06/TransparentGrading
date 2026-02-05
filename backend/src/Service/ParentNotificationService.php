<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ParentNotificationService
{
    public function __construct(
        private Connection $conn,
        private MailerInterface $mailer
    ) {
    }

    public function checkAndNotify(int $schuelerId, int $kursId): void
    {
        // 1) Gesamtnote berechnen
        $rows = array_merge(
            $this->getAufgabenNoten($schuelerId, $kursId),
            $this->getBenotungNoten($schuelerId, $kursId)
        );

        $sum = 0.0;
        $weight = 0.0;

        foreach ($rows as $r) {
            if ($r['note'] === null || $r['gewichtung'] <= 0) {
                continue;
            }
            $sum += $r['note'] * $r['gewichtung'];
            $weight += $r['gewichtung'];
        }

        if ($weight === 0.0) {
            return;
        }

        $schnitt = round($sum / $weight, 2);

        // 2) Schwelle für Benachrichtigung (z.B. Note >= 4.5)
        if ($schnitt < 4.5) {
            return;
        }

        // 3) Elterninfo laden
        $info = $this->conn->fetchAssociative("
    SELECT
        s.vorname,
        s.nachname,
        e.elternemail,
        k.name AS kurs,
        f.name AS fach,
        l.vorname AS l_vorname,
        l.nachname AS l_nachname,
        mu.email AS lehrer_email
    FROM Schueler s
    INNER JOIN Einstellungen e ON e.id = s.ms365usr_id
    INNER JOIN Kurse k ON k.id = :kid
    INNER JOIN Faecher f ON f.id = k.fach_id
    INNER JOIN Lehrer l ON l.id = k.lehrer_id
    INNER JOIN tbl_Microsoft365_User mu ON mu.ID = l.MS365Usr_ID
    WHERE s.id = :sid
      AND e.ElternAktivierung = 1
", [
            'sid' => $schuelerId,
            'kid' => $kursId
        ]);

        echo json_encode($info);
        
        if (!$info || !$info['elternemail']) {
            return; // Keine Elternmail vorhanden
        }

        // 4) Mail senden
        $mail = (new Email())
            ->from('1033@htl.rennweg.at')
            ->replyTo($info['lehrer_email'])
            ->to($info['elternemail'])
            ->subject('Leistungsinformation – ' . $info['fach'])
            ->text(sprintf(
                "Sehr geehrte Erziehungsberechtigte,\n\n" .
                "Ihr Kind %s %s steht aktuell im Fach %s auf der Note %.2f.\n\n" .
                "Mit freundlichen Grüßen\n%s %s",
                $info['vorname'],
                $info['nachname'],
                $info['fach'],
                $schnitt,
                $info['l_vorname'],
                $info['l_nachname']
            ));

        $this->mailer->send($mail);
    }

    private function getAufgabenNoten(int $sid, int $kid): array
    {
        return $this->conn->fetchAllAssociative("
            SELECT
                ab.note,
                COALESCE(a.gewichtung_prozent, ba.gewichtung) AS gewichtung
            FROM Aufgaben_Bewertung ab
            INNER JOIN Aufgaben a ON a.id = ab.aufgabe_id
            LEFT JOIN Benotungsarten ba ON ba.id = a.benotungsart_id
            WHERE ab.schueler_id = :sid
              AND a.kurs_id = :kid
        ", ['sid' => $sid, 'kid' => $kid]);
    }

    private function getBenotungNoten(int $sid, int $kid): array
    {
        return $this->conn->fetchAllAssociative("
            SELECT
                b.note,
                ba.gewichtung
            FROM Benotung b
            INNER JOIN Benotungsarten ba ON ba.id = b.typ
            WHERE b.schueler_id = :sid
              AND b.fach_id = (
                  SELECT fach_id FROM Kurse WHERE id = :kid
              )
        ", ['sid' => $sid, 'kid' => $kid]);
    }
}
