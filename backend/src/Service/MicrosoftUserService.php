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
        // --- M365-User in Haupttabelle suchen ---
        $existingUser = $this->em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $email]);

        // Falls noch nicht vorhanden → anlegen
        if (!$existingUser) {
            $existingUser = new Microsoft365User();
            $existingUser->setVorname($vorname);
            $existingUser->setNachname($nachname);
            $existingUser->setEmail($email);

            // Falls es die Felder in der Entity gibt:
            if (method_exists($existingUser, 'setLizenzen')) {
                $existingUser->setLizenzen('');
            }
            if (method_exists($existingUser, 'setProxyadressen')) {
                $existingUser->setProxyadressen('');
            }

            $this->em->persist($existingUser);
            $this->em->flush();
        }

        // --- Rolle aus der Mail bestimmen ---
        // 1034@htl.rennweg.at  → Schüler
        // ABC@htl.rennweg.at   → Lehrer
        $emailLower = strtolower($email);
        [$localPart] = explode('@', $emailLower);

        if (preg_match('/^[0-9]{4}$/', $localPart)) {
            $this->ensureSchueler($existingUser, $vorname, $nachname);
            return 'Schueler';
        }

        if (preg_match('/^[a-z]{3}$/', $localPart)) {
            $this->ensureLehrer($existingUser, $vorname, $nachname);
            return 'Lehrer';
        }

        return 'Unbekannt';
    }

    /**
     * Stellt sicher, dass es zu diesem Microsoft365User einen Schüler-Datensatz gibt.
     */
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


    /**
     * Stellt sicher, dass es zu diesem Microsoft365User einen Lehrer-Datensatz gibt.
     */
    private function ensureLehrer(Microsoft365User $m365User, string $vorname, string $nachname): void
    {
        $lehrer = $this->em->getRepository(Lehrer::class)
            ->findOneBy(['ms365usr_id' => $m365User->getId()]);

        if ($lehrer) {
            return;
        }

        $lehrer = new Lehrer();
        if (method_exists($lehrer, 'setVorname')) {
            $lehrer->setVorname($vorname);
        }
        if (method_exists($lehrer, 'setNachname')) {
            $lehrer->setNachname($nachname);
        }

        if (method_exists($lehrer, 'setMicrosoftUser')) {
            $lehrer->setMicrosoftUser($m365User);
        } elseif (method_exists($lehrer, 'setMs365usr')) {
            $lehrer->setMs365usr($m365User);
        }

        $this->em->persist($lehrer);
        $this->em->flush();
    }
}
