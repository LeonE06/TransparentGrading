<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Kurse;

#[Route('/api')]
class StudentGradesController extends AbstractController
{
    #[Route('/schueler/faecher/{kursId}/noten', methods: ['GET'])]
    public function getNoten(
        int $kursId,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {

        // ------------------------------------------------
        // 🔧 DEBUG-MODUS (temporär: arbeitet ohne Login!)
        // ------------------------------------------------
        $DEBUG = true;  // <-- später auf false setzen, wenn MS Login fertig!

        if ($DEBUG) {
            // Nutze Schüler mit ID = 1
            $schueler = $em->getRepository(\App\Entity\Schueler::class)->find(1);

            if (!$schueler) {
                return new JsonResponse([
                    'error' => 'DEBUG FEHLER: Schüler mit ID 1 existiert nicht.'
                ], 500);
            }
        } else {
            // ------------------------------------------------
            // 🔒 ORIGINALER MODE (Microsoft Login)
            // ------------------------------------------------
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'Not authorized'], 401);
            }

            $schueler = $em->getRepository(\App\Entity\Schueler::class)
                ->findOneBy(['ms365User' => $user->getId()]);

            if (!$schueler) {
                return new JsonResponse(['error' => 'Schüler nicht gefunden'], 404);
            }
        }

        // ------------------------------------------------
        // 1) Hole alle Noten zu diesem Fach/Kurs
        // ------------------------------------------------
        $noten = $em->getConnection()->executeQuery("
            SELECT 
                b.id,
                b.datum,
                b.note,
                ba.name AS typ_name,
                ba.gewichtung,
                b.kommentar
            FROM Benotung b
            LEFT JOIN Benotungsarten ba ON ba.id = b.typ
            WHERE b.schueler_id = :sid
              AND b.fach_id = (SELECT fach_id FROM Kurse WHERE id = :kid)
            ORDER BY b.datum ASC
        ", [
            'sid' => $schueler->getId(),
            'kid' => $kursId
        ])->fetchAllAssociative();


        // ------------------------------------------------
        // 2) Berechne Schüler-Durchschnitt
        // ------------------------------------------------
        $schuelerDurchschnitt = $em->getConnection()->executeQuery("
            SELECT AVG(note) AS avg
            FROM Benotung
            WHERE schueler_id = :sid
              AND fach_id = (SELECT fach_id FROM Kurse WHERE id = :kid)
        ", [
            'sid' => $schueler->getId(),
            'kid' => $kursId
        ])->fetchOne();


        // ------------------------------------------------
        // 2.1) Sende Email an Eltern wenn Notenstand schlechter als 4.5
        // ------------------------------------------------
        // Schwelle: ab Durchschnitt 4.5
        if ($schuelerDurchschnitt !== null && (float) $schuelerDurchschnitt >= 4.5) {
            // Einstellungen / Elternmail
            $einstellungen = $schueler->getEinstellungen();
            $elternEmail = $einstellungen?->getElternemail();
            $elternAktiviert = $einstellungen?->getElternaktivierung();

            if ($elternEmail && $elternAktiviert) {
                // Kurs + Lehrer
                $kurs = $em->getRepository(Kurse::class)->find($kursId);
                $lehrer = $kurs?->getLehrer();
                $lehrerEmail = $lehrer?->getMs365User()?->getEmail();

                if ($lehrerEmail) {
                    $fachName = $kurs?->getFach()?->getName() ?? 'Fach';

                    $mail = (new Email())
                        ->from('1033@htl.rennweg.at')
                        ->replyTo($lehrerEmail)
                        ->to($elternEmail)
                        ->subject(sprintf(
                            'Leistungsinformation %s – %s %s',
                            $fachName,
                            $schueler->getVorname(),
                            $schueler->getNachname()
                        ))
                        ->text(
                            sprintf(
                                "Sehr geehrte Ehrziehungsberechtigte,\n\n" .
                                "Ihr Kind %s %s steht aktuell in %s auf der Note %.2f.\n" .
                                "Bitte setzen Sie sich mit mir in Verbindung, falls Sie Fragen haben.\n\n" .
                                "Mit freundlichen Grüßen\n%s %s",
                                $schueler->getVorname(),
                                $schueler->getNachname(),
                                $fachName,
                                (float) $schuelerDurchschnitt,
                                $lehrer?->getVorname() ?? '',
                                $lehrer?->getNachname() ?? ''
                            )
                        );

                    $mailer->send($mail);
                }
            }
        }

        // ------------------------------------------------
        // 3) Berechne Klassenschnitt
        // ------------------------------------------------
        $klassenDurchschnitt = $em->getConnection()->executeQuery("
            SELECT AVG(b.note) AS avg
            FROM Benotung b
            WHERE b.fach_id = (SELECT fach_id FROM Kurse WHERE id = :kid)
        ", [
            'kid' => $kursId
        ])->fetchOne();


        // ------------------------------------------------
        // 4) JSON Rückgabe
        // ------------------------------------------------
        return new JsonResponse([
            'noten' => $noten,
            'schueler_notenstand' => $schuelerDurchschnitt ? round($schuelerDurchschnitt, 2) : null,
            'klassenschnitt' => $klassenDurchschnitt ? round($klassenDurchschnitt, 2) : null,
        ]);
    }
}
