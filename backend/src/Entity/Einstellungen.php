<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Einstellungen')]
class Einstellungen
{
    /**
     * Primärschlüssel = schueler_id (OneToOne zu Schueler)
     */
#[ORM\Id]
#[ORM\OneToOne(targetEntity: Schueler::class, inversedBy: 'einstellungen')]
#[ORM\JoinColumn(
    name: 'schueler_id',
    referencedColumnName: 'id',
    nullable: false,
    onDelete: 'CASCADE'
)]
private Schueler $schueler;


    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $sprache = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $elternemail = null;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $elternaktivierung = false;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $benachrichtigungen = false;

    #[ORM\Column(
        name: 'mood_benachrichtigung',
        type: 'boolean',
        options: ['default' => 1]
    )]
    private bool $moodBenachrichtigung = true;

    #[ORM\Column(
        name: 'light_darkmode',
        type: 'boolean',
        options: ['default' => 0]
    )]
    private bool $lightDarkmode = false;

    /* ==========================
       Getter / Setter
       ========================== */

    public function getSchueler(): Schueler
    {
        return $this->schueler;
    }

    public function setSchueler(Schueler $schueler): self
    {
        $this->schueler = $schueler;
        return $this;
    }

    /**
     * Optional: Convenience-Getter für die ID des Schülers
     */
    public function getSchuelerId(): int
    {
        return $this->schueler->getId();
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

    public function isElternaktivierung(): bool
    {
        return $this->elternaktivierung;
    }

    public function setElternaktivierung(bool $elternaktivierung): self
    {
        $this->elternaktivierung = $elternaktivierung;
        return $this;
    }

    public function isBenachrichtigungen(): bool
    {
        return $this->benachrichtigungen;
    }

    public function setBenachrichtigungen(bool $benachrichtigungen): self
    {
        $this->benachrichtigungen = $benachrichtigungen;
        return $this;
    }

    public function isMoodBenachrichtigung(): bool
    {
        return $this->moodBenachrichtigung;
    }

    public function setMoodBenachrichtigung(bool $enabled): self
    {
        $this->moodBenachrichtigung = $enabled;
        return $this;
    }

    public function isLightDarkmode(): bool
    {
        return $this->lightDarkmode;
    }

    public function setLightDarkmode(bool $lightDarkmode): self
    {
        $this->lightDarkmode = $lightDarkmode;
        return $this;
    }
}
