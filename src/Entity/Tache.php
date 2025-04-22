<?php

namespace App\Entity;

use App\Repository\TacheRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TacheRepository::class)]
class Tache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_tache;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description;

    #[ORM\Column(type: 'string', enumType: StatutTache::class)]
    private ?StatutTache $status;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $created_at;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "id_employe", referencedColumnName: "id_employe", nullable: false)]
    private ?User $user;

    #[ORM\Column(type: 'string', enumType: Priorite::class)]
    private ?Priorite $priority;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La location est obligatoire.')]
    private ?string $location;

    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: "taches")]
    #[ORM\JoinColumn(name: "id_projet", referencedColumnName: "id_projet")]
    private ?Projet $projet;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    // Helper method to get the employee ID
    public function getIdEmploye(): ?int
    {
        return $this->user ? $this->user->getId() : null;
    }

    // Helper method to get the employee's full name
    public function getEmployeFullName(): ?string
    {
        return $this->user ? $this->user->getFirstname() . ' ' . $this->user->getLastname() : null;
    }

    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    public function setProjet(?Projet $projet): self
    {
        $this->projet = $projet;
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
            "%s (%s) - Créé par: %s",
            $this->description,
            $this->status ? (string) $this->status->getLabel() : 'Statut inconnu',
            $this->getEmployeFullName() ?? 'Employé inconnu'
        );
    }
}