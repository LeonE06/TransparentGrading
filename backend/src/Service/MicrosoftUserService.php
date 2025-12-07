<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Schueler;
use App\Entity\Lehrer;
use App\Entity\TblMicrosoft365User;

class MicrosoftUserService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Speichert Microsoft Benutzer & bestimmt Rolle (lehrer / schueler)
     */
    public function handleMicrosoftUser(string $vorname, string $nachname, string $email): string
    {
        // 🔍 DEBUG: E-Mail ausgeben
        // file_put_contents('/tmp/auth_debug.log', "EMAIL: $email\n", FILE_APPEND);

        // Nutzer in Haupttabelle suchen
        $existingUser = $this->em->getRepository(TblMicrosoft365User::class)
            ->findOneBy(['email' => $email]);

        if (!$existingUser) {
            $existingUser = new TblMicrosoft365User();
            $existingUser->setVorname($vorname);
            $existingUser->setNachname($nachname);
            $existingUser->setEmail($email);

            $this->em->persist($existingUser);
            $this->em->flush();
        }

        // 🔍 DEBUG: Loggen
        // file_put_contents('/tmp/auth_debug.log', "CHECK ROLE FOR: $email\n", FILE_APPEND);

        // EMAIL → ROLLENERKENNUNG
        // Schüler → z.B. 1034@htl.rennweg.at
        if (preg_match('/^[0-9]{4}@htl\.rennweg\.at$/i', $email)) {
            // file_put_contents('/tmp/auth_debug.log', "ROLE: SCHUELER\n", FILE_APPEND);
            return $this->ensureSchueler($existingUser, $vorname, $nachname);
        }

        // Lehrer → z.B. ABC@htl.rennweg.at
        if (preg_match('/^[A-Za-z]{3}@htl\.rennweg\.at$/i', $email)) {
            // file_put_contents('/tmp/auth_debug.log', "ROLE: LEHRER\n", FILE_APPEND);
            return $this->ensureLehrer($existingUser, $vorname, $nachname);
        }

        // file_put_contents('/tmp/auth_debug.log', "ROLE: UNKNOWN\n", FILE_APPEND);
        return 'Unbekannt';
    }


    private function ensureSchueler(TblMicrosoft365User $m365User, string $vorname, string $nachname): string
    {
        $schueler = $this->em->getRepository(Schueler::class)
            ->findOneBy(['ms365usr_id' => $m365User->getId()]);

        if (!$schueler) {
            $schueler = new Schueler();
            $schueler->setVorname($vorname);
            $schueler->setNachname($nachname);
            $schueler->setMicrosoftUser($m365User);

            $this->em->persist($schueler);
            $this->em->flush();
        }

        return "Schueler";
    }


    private function ensureLehrer(TblMicrosoft365User $m365User, string $vorname, string $nachname): string
    {
        $lehrer = $this->em->getRepository(Lehrer::class)
            ->findOneBy(['ms365usr_id' => $m365User->getId()]);

        if (!$lehrer) {
            $lehrer = new Lehrer();
            $lehrer->setVorname($vorname);
            $lehrer->setNachname($nachname);
            $lehrer->setMicrosoftUser($m365User);

            $this->em->persist($lehrer);
            $this->em->flush();
        }

        return "Lehrer";
    }
}
