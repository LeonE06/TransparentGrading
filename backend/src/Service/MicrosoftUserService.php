<?php

namespace App\Service;

use App\Entity\Lehrer;
use App\Entity\Microsoft365User;
use App\Entity\Schueler;
use Doctrine\ORM\EntityManagerInterface;

class MicrosoftUserService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Speichert den Microsoft-Benutzer (falls noetig) und gibt alle ermittelten Rollen zurueck.
     *
     * @return string[]
     */
    public function handleMicrosoftUser(string $vorname, string $nachname, string $email): array
    {
        $existingUser = $this->em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $email]);

        if (!$existingUser) {
            $existingUser = new Microsoft365User();
            $existingUser->setVorname($vorname);
            $existingUser->setNachname($nachname);
            $existingUser->setEmail($email);

            if (method_exists($existingUser, 'setLizenzen')) {
                $existingUser->setLizenzen('');
            }
            if (method_exists($existingUser, 'setProxyadressen')) {
                $existingUser->setProxyadressen('');
            }

            $this->em->persist($existingUser);
            $this->em->flush();
        }

        $roles = [];
        $emailLower = strtolower($email);
        [$localPart] = explode('@', $emailLower);

        if (preg_match('/^[a-z]{3}$/', $localPart)) {
            $lehrer = $this->ensureLehrer($existingUser, $vorname, $nachname);
            $roles[] = 'Lehrer';

            if ($lehrer->getIsAdmin()) {
                $roles[] = 'Admin';
            }
        }

        if (preg_match('/^[0-9]{4}$/', $localPart)) {
            $this->ensureSchueler($existingUser, $vorname, $nachname);
            $roles[] = 'Schueler';
        }

        if ($roles === []) {
            $roles[] = 'Unbekannt';
        }

        return array_values(array_unique($roles));
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

    private function ensureLehrer(Microsoft365User $m365User, string $vorname, string $nachname): Lehrer
    {
        $lehrer = $this->em->getRepository(Lehrer::class)
            ->findOneBy(['ms365User' => $m365User]);

        if ($lehrer) {
            return $lehrer;
        }

        $lehrer = new Lehrer();
        $lehrer->setVorname($vorname);
        $lehrer->setNachname($nachname);
        $lehrer->setMs365User($m365User);

        $this->em->persist($lehrer);
        $this->em->flush();

        return $lehrer;
    }
}
