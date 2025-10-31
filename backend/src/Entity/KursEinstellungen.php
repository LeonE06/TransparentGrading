<?php

namespace App\Entity;

use App\Repository\KursEinstellungenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KursEinstellungenRepository::class)]
#[ORM\Table(name: "Kurs_Einstellungen")]
class KursEinstellungen
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Schueler::class, inversedBy: 'kursEinstellungen')]
    #[ORM\JoinColumn(name: "schueler_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Schueler $schueler = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Kurse::class, inversedBy: 'kursEinstellungen')]
    #[ORM\JoinColumn(name: "kurs_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Kurse $kurs = null;

    #[ORM\Column(type: "boolean", options: ["default" => 0])]
    private bool $benachrichtigung = false;

    #[ORM\Column(type: "boolean", options: ["default" => 1])]
    private bool $sichtbar = true;

    // --- Getter & Setter ---

    public function getSchueler(): ?Schueler
    {
        return $this->schueler;
    }

    public function setSchueler(?Schueler $schueler): self
    {
        $this->schueler = $schueler;
        return $this;
    }

    public function getKurs(): ?Kurse
    {
        return $this->kurs;
    }

    public function setKurs(?Kurse $kurs): self
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
