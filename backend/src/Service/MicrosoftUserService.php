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
    $emailLower = strtolower($email);
    [$localPart] = explode('@', $emailLower);

    $isAdmin = false; // Default isAdmin value

    // Check if user is admin by is_admin field in the database
    if ($existingUser->getIsAdmin()) {
        $isAdmin = true;
    }

    // Schüler-Mail (4 Ziffern) → Lehrer
    if (preg_match('/^[0-9]{4}$/', $localPart)) {
        $this->ensureLehrer($existingUser, $vorname, $nachname);
        return 'Lehrer';
    }

    // Email in the form of 3 characters → Schueler
    if (preg_match('/^[a-z]{3}$/', $localPart)) {
        $this->ensureSchueler($existingUser, $vorname, $nachname);
        return 'Schueler';
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
            ->findOneBy(['ms365User' => $m365User]);

        if ($lehrer)
            return;

        $lehrer = new Lehrer();
        $lehrer->setVorname($vorname);
        $lehrer->setNachname($nachname);
        $lehrer->setMs365User($m365User);

        $this->em->persist($lehrer);
        $this->em->flush();
    }
}
