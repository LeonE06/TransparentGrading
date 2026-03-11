<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\LehrerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LehrerRepository::class)]
#[ORM\Table(name: "Lehrer")]
class Lehrer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = NULL;

    #[ORM\Column(length: 100)]
    private string $vorname;

    #[ORM\Column(length: 100)]
    private string $nachname;

    #[ORM\ManyToOne(targetEntity: Microsoft365User::class)]
    #[ORM\JoinColumn(name: "ms365usr_id", referencedColumnName: "id", nullable: false)]
    private ?Microsoft365User $ms365User = NULL;

    #[ORM\OneToMany(mappedBy: 'lehrer', targetEntity: Kurse::class)]
    private Collection $kurse;

    #[ORM\OneToMany(mappedBy: 'lehrer', targetEntity: LehrerFach::class, orphanRemoval: true)]
    private Collection $lehrerFaecher;

    // Hinzufügen der `is_admin`-Eigenschaft
    #[ORM\Column(type: "boolean", options: ["default" => "false"])] 
private bool $is_admin = false;

    // ----- Getter & Setter -----

    public function __construct()
    {
        $this->kurse = new ArrayCollection();
        $this->lehrerFaecher = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVorname(): string
    {
        return $this->vorname;
    }

    public function setVorname(string $vorname): self
    {
        $this->vorname = $vorname;
        return $this;
    }

    public function getNachname(): string
    {
        return $this->nachname;
    }

    public function setNachname(string $nachname): self
    {
        $this->nachname = $nachname;
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

    public function getKurse(): Collection
    {
        return $this->kurse;
    }

    public function getLehrerFaecher(): Collection
    {
        return $this->lehrerFaecher;
    }

    public function addLehrerFach(LehrerFach $lehrerFach): self
    {
        if (!$this->lehrerFaecher->contains($lehrerFach)) {
            $this->lehrerFaecher->add($lehrerFach);
            $lehrerFach->setLehrer($this);
        }

        return $this;
    }

    public function removeLehrerFach(LehrerFach $lehrerFach): self
    {
        if ($this->lehrerFaecher->removeElement($lehrerFach)) {
            if ($lehrerFach->getLehrer() === $this) {
                $lehrerFach->setLehrer(null);
            }
        }

        return $this;
    }

    // Getter & Setter für `is_admin`
    public function getIsAdmin(): bool
    {
        return $this->is_admin;
    }

    public function setIsAdmin(bool $is_admin): self
    {
        $this->is_admin = $is_admin;
        return $this;
    }
}
