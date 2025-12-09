<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use App\Entity\Lehrer;

class MicrosoftUserService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Speichert den Microsoft-Benutzer (falls nötig) und gibt die Rolle zurück.
     *
     * @return string "Schueler" | "Lehrer" | "Unbekannt"
     */
    public function handleMicrosoftUser(string $vorname, string $nachname, string $email): string
    {
        // Microsoft365User heraussuchen oder erstellen
        $existingUser = $this->em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $email]);

        if (!$existingUser) {
            $existingUser = new Microsoft365User();
            $existingUser->setVorname($vorname);
            $existingUser->setNachname($nachname);
            $existingUser->setEmail($email);
            $existingUser->setLizenzen('');
            $existingUser->setProxyadressen('');

            $this->em->persist($existingUser);
            $this->em->flush();
        }

        // Rolle bestimmen durch Email
        $emailLower = strtolower($email);
        [$localPart] = explode('@', $emailLower);

        // Schüler: 4 Zahlen
        if (preg_match('/^[0-9]{4}$/', $localPart)) {
            $this->ensureSchueler($existingUser, $vorname, $nachname);
            return 'Schueler';
        }

        // Lehrer: 3 Buchstaben oder vorname.nachname
        if (
            preg_match('/^[a-z]{3}$/', $localPart) ||
            preg_match('/^[a-z]+\.[a-z]+$/', $localPart)
        ) {
            $this->ensureLehrer($existingUser, $vorname, $nachname);
            return 'Lehrer';
        }

        return 'Unbekannt';
    }


    private function ensureSchueler(Microsoft365User $m365User, string $vorname, string $nachname): void
    {
        $schueler = $this->em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $m365User]);

        if ($schueler) {
            return;
        }

        $schueler = new Schueler();
        $schueler->setVorname($vorname);
        $schueler->setNachname($nachname);
        $schueler->setMs365User($m365User);

        $this->em->persist($schueler);
        $this->em->flush();
    }


    private function ensureLehrer(Microsoft365User $m365User, string $vorname, string $nachname): void
    {
        $lehrer = $this->em->getRepository(Lehrer::class)
            ->findOneBy(['ms365usr' => $m365User]); // <-- korrektes Feld

        if ($lehrer) {
            return;
        }

        $lehrer = new Lehrer();
        $lehrer->setVorname($vorname);
        $lehrer->setNachname($nachname);
        $lehrer->setMs365usr($m365User); // <-- korrektes Setter

        $this->em->persist($lehrer);
        $this->em->flush();
    }
}
