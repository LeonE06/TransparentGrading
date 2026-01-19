<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Einstellungen')]
class Einstellungen
{
    /**
     * Primärschlüssel = schueler_id
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $sprache = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $elternemail = null;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $elternaktivierung = false;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $benachrichtigungen = false;

    // ✅ NEU: Mood-Benachrichtigung
    #[ORM\Column(
        name: 'mood_benachrichtigung',
        type: 'boolean',
        options: ['default' => 1]
    )]
    private bool $moodBenachrichtigung = true;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $light_darkmode = false;

    /* ==========================
       Getter / Setter
       ========================== */

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
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

    // ✅ Mood Reminder
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
        return $this->light_darkmode;
    }

    public function setLightDarkmode(bool $light_darkmode): self
    {
        $this->light_darkmode = $light_darkmode;
        return $this;
    }
}
