<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ParentNotificationService
{
    public function __construct(
        private Connection $conn,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        #[Autowire('%mail_from_address%')]
        private string $fromAddress,
        #[Autowire('%mail_from_name%')]
        private string $fromName,
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

        // 3) Schüler- und Kursinfo laden
        $info = $this->conn->fetchAssociative("
            SELECT
                s.vorname,
                s.nachname,
                e.elternemail,
                e.ElternAktivierung AS elternaktivierung,
                m365.email AS schueler_email,
                k.name AS kurs,
                f.name AS fach,
                l.vorname AS l_vorname,
                l.nachname AS l_nachname,
                mu.email AS lehrer_email
            FROM Schueler s
            LEFT JOIN Einstellungen e ON e.schueler_id = s.id
            LEFT JOIN tbl_Microsoft365_User m365 ON m365.ID = s.ms365usr_id
            INNER JOIN Kurse k ON k.id = :kid
            INNER JOIN Faecher f ON f.id = k.fach_id
            INNER JOIN Lehrer l ON l.id = k.lehrer_id
            LEFT JOIN tbl_Microsoft365_User mu ON mu.ID = l.MS365Usr_ID
            WHERE s.id = :sid
        ", [
            'sid' => $schuelerId,
            'kid' => $kursId
        ]);

        if (!$info) {
            return;
        }

        $fach = $info['fach'];
        $schuelerName = $info['vorname'] . ' ' . $info['nachname'];
        $lehrerName = $info['l_vorname'] . ' ' . $info['l_nachname'];

        // 4) E-Mail an Schüler senden
        if (!empty($info['schueler_email'])) {
            $this->sendMail(
                $info['schueler_email'],
                'Notenwarnung – ' . $fach,
                sprintf(
                    "Hallo %s,\n\n" .
                    "dein aktueller Notendurchschnitt im Fach %s (Kurs: %s) beträgt %.2f " .
                    "und entspricht damit der Note Nicht Genügend (5).\n\n" .
                    "Bitte melde dich in Transparent Grading an und prüfe deine Noten.\n\n" .
                    "Mit freundlichen Grüßen\n%s",
                    $info['vorname'],
                    $fach,
                    $info['kurs'],
                    $schnitt,
                    $lehrerName
                ),
                $info['lehrer_email']
            );
        }

        // 5) E-Mail an Eltern senden (falls aktiviert)
        if (!empty($info['elternaktivierung']) && !empty($info['elternemail'])) {
            $this->sendMail(
                $info['elternemail'],
                'Leistungsinformation – ' . $fach,
                sprintf(
                    "Sehr geehrte Erziehungsberechtigte,\n\n" .
                    "Ihr Kind %s steht aktuell im Fach %s (Kurs: %s) auf der Note %.2f " .
                    "und entspricht damit der Note Nicht Genügend (5).\n\n" .
                    "Mit freundlichen Grüßen\n%s",
                    $schuelerName,
                    $fach,
                    $info['kurs'],
                    $schnitt,
                    $lehrerName
                ),
                $info['lehrer_email']
            );
        }
    }

    private function sendMail(string $to, string $subject, string $text, ?string $replyTo = null): void
    {
        try {
            $mail = (new Email())
                ->from("{$this->fromName} <{$this->fromAddress}>")
                ->to($to)
                ->subject($subject)
                ->text($text);

            if ($replyTo) {
                $mail->replyTo($replyTo);
            }

            $this->mailer->send($mail);
            $this->logger->info("Notenwarnung gesendet an {$to}: {$subject}");
        } catch (\Exception $e) {
            $this->logger->error("Mail-Versand fehlgeschlagen an {$to}: " . $e->getMessage());
        }
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
