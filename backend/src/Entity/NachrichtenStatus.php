<?php

namespace App\Entity;

use App\Repository\NachrichtenStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NachrichtenStatusRepository::class)]
#[ORM\Table(name: 'Nachrichten_Status')]
class NachrichtenStatus
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'status')]
    #[ORM\JoinColumn(name: 'nachricht_id', referencedColumnName: 'id')]
    private ?Nachrichten $nachricht = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'nachrichtenStatus')]
    #[ORM\JoinColumn(name: 'schueler_id', referencedColumnName: 'id')]
    private ?Schueler $schueler = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $gelesen = false;

    public function getNachricht(): ?Nachrichten
    {
        return $this->nachricht;
    }

    public function setNachricht(?Nachrichten $nachricht): self
    {
        $this->nachricht = $nachricht;
        return $this;
    }

    public function getSchueler(): ?Schueler
    {
        return $this->schueler;
    }

    public function setSchueler(?Schueler $schueler): self
    {
        $this->schueler = $schueler;
        return $this;
    }

    public function isGelesen(): bool
    {
        return $this->gelesen;
    }

    public function setGelesen(bool $gelesen): self
    {
        $this->gelesen = $gelesen;
        return $this;
    }
}
