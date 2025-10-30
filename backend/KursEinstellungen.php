<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Schueler;
use App\Entity\Kurse;

#[ORM\Entity]
#[ORM\Table(name: 'Kurs_Einstellungen')]
class KursEinstellungen
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Schueler::class, inversedBy: 'kursEinstellungen')]
    #[ORM\JoinColumn(nullable: false)]
    private Schueler $schueler;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Kurse::class, inversedBy: 'kursEinstellungen')]
    #[ORM\JoinColumn(nullable: false)]
    private Kurse $kurs;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $benachrichtigung = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $sichtbar = true;

    public function getSchueler(): Schueler
    {
        return $this->schueler;
    }

    public function setSchueler(Schueler $schueler): self
    {
        $this->schueler = $schueler;
        return $this;
    }

    public function getKurs(): Kurse
    {
        return $this->kurs;
    }

    public function setKurs(Kurse $kurs): self
    {
        $this->kurs = $kurs;
        return $this;
    }

    public function isBenachrichtigung(): bool
    {
        return $this->benachrichtigung;
    }

    public function setBenachrichtigung(bool $benachrichtigung): self
    {
        $this->benachrichtigung = $benachrichtigung;
        return $this;
    }

    public function isSichtbar(): bool
    {
        return $this->sichtbar;
    }

    public function setSichtbar(bool $sichtbar): self
    {
        $this->sichtbar = $sichtbar;
        return $this;
    }
}
