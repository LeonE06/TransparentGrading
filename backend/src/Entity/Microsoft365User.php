<?php

namespace App\Entity;

use App\Repository\Microsoft365UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Microsoft365UserRepository::class)]
#[ORM\Table(name: "tbl_Microsoft365_User")]
class Microsoft365User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $id = NULL;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nachname = NULL;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $vorname = NULL;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email = NULL;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lizenzen = NULL;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $proxyadressen = NULL;

    #[ORM\Column(type: "datetime", nullable: true)]
    private \DateTimeInterface $erstellungszeitpunkt;

    // ----- Getter & Setter -----

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNachname(): ?string
    {
        return $this->nachname;
    }

    public function setNachname(?string $nachname): self
    {
        $this->nachname = $nachname;
        return $this;
    }

    public function getVorname(): ?string
    {
        return $this->vorname;
    }

    public function setVorname(?string $vorname): self
    {
        $this->vorname = $vorname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getLizenzen(): ?string
    {
        return $this->lizenzen;
    }

    public function setLizenzen(?string $lizenzen): self
    {
        $this->lizenzen = $lizenzen;
        return $this;
    }

    public function getProxyadressen(): ?string
    {
        return $this->proxyadressen;
    }

    public function setProxyadressen(?string $proxyadressen): self
    {
        $this->proxyadressen = $proxyadressen;
        return $this;
    }

    public function getErstellungszeitpunkt(): \DateTimeInterface
    {
        return $this->erstellungszeitpunkt;
    }

    public function setErstellungszeitpunkt(\DateTimeInterface $erstellungszeitpunkt): self
    {
        $this->erstellungszeitpunkt = $erstellungszeitpunkt;
        return $this;
    }
    public function getMs365User(): ?Microsoft365User
{
    return $this->ms365User;
}

public function setMs365User(?Microsoft365User $ms365User): self
{
    $this->ms365User = $ms365User;
    return $this;
}
}
