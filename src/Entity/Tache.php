<?php
namespace App\Entity;

use App\Repository\TacheRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TacheRepository::class)]
class Tache {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_tache = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: StatutTache::class)]
    private ?StatutTache $status = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id_employe = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id_projet = null;

    #[ORM\Column(type: 'string', enumType: Priorite::class)]
    private ?Priorite $priority = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    // Getters et Setters
    public function getIdTache(): ?int 
    {
        return $this->id_tache;
    }

    public function getDescription(): ?string 
    {
        return $this->description;
    }

    public function setDescription(string $description): static 
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): ?StatutTache 
    {
        return $this->status;
    }

    public function setStatus(StatutTache $status): static 
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface 
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static 
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getIdEmploye(): ?int 
    {
        return $this->id_employe;
    }

    public function setIdEmploye(?int $id_employe): static 
    {
        $this->id_employe = $id_employe;
        return $this;
    }

    public function getIdProjet(): ?int 
    {
        return $this->id_projet;
    }

    public function setIdProjet(?int $id_projet): static 
    {
        $this->id_projet = $id_projet;
        return $this;
    }

    public function getPriority(): ?Priorite 
    {
        return $this->priority;
    }

    public function setPriority(Priorite $priority): static 
    {
        $this->priority = $priority;
        return $this;
    }

    public function getLocation(): ?string 
    {
        return $this->location;
    }

    public function setLocation(?string $location): static 
    {
        $this->location = $location;
        return $this;
    }

    public function __toString(): string 
    {
        return sprintf(
            "%s (%s)", 
            $this->description,
            $this->status ? (string) $this->status->getLabel() : 'Statut inconnu'
        );
    }
}