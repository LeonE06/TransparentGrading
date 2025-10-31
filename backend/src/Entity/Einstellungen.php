<?php

namespace App\Entity;

use App\Repository\EinstellungenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EinstellungenRepository::class)]
#[ORM\Table(name: "Einstellungen")]
class Einstellungen
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'einstellungen', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id')]
    private ?Schueler $schueler = NULL;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sprache = NULL;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $elternemail = NULL;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $elternaktivierung = NULL;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $benachrichtigungen = NULL;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $light_darkmode = NULL;

    public function getSchueler(): ?Schueler
    {
        return $this->schueler;
    }

    public function setSchueler(?Schueler $schueler): self
    {
        $this->schueler = $schueler;
        return $this;
    }

    public function getSprache(): ?string
    {
        return $this->sprache;
    }

    public function setSprache(?string $sprache): self
    {
        $this->sprache = $sprache;
        return $this;
    }

    public function getElternemail(): ?string
    {
        return $this->elternemail;
    }

    public function setElternemail(?string $elternemail): self
    {
        $this->elternemail = $elternemail;
        return $this;
    }

    public function getElternaktivierung(): ?bool
    {
        return $this->elternaktivierung;
    }

    public function setElternaktivierung(?bool $elternaktivierung): self
    {
        $this->elternaktivierung = $elternaktivierung;
        return $this;
    }

    public function getBenachrichtigungen(): ?bool
    {
        return $this->benachrichtigungen;
    }

    public function setBenachrichtigungen(?bool $benachrichtigungen): self
    {
        $this->benachrichtigungen = $benachrichtigungen;
        return $this;
    }

    public function getLightDarkmode(): ?bool
    {
        return $this->light_darkmode;
    }

    public function setLightDarkmode(?bool $light_darkmode): self
    {
        $this->light_darkmode = $light_darkmode;
        return $this;
    }
}
