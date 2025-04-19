<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;
use App\Entity\Status;

#[ORM\Entity]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idOffre;

    #[Assert\NotBlank(message: "🚨 Le titre est obligatoire.")]
    #[Assert\Length(min: 3, minMessage: "Le titre doit contenir au moins {{ 5 }} caractères.")]
    #[ORM\Column(length: 255)]
    private ?string $titre ;

    #[Assert\NotBlank(message: " 🚨 La description est requise.")]
    #[Assert\Length(min: 10, minMessage: "La description doit être plus détaillée.")]
    #[ORM\Column(type: 'text')]
    private ?string $description ;

    #[Assert\NotNull(message: " 🚨 La date de publication est obligatoire.")]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $datePublication;

    #[Assert\NotNull(message: " 🚨 La date d’expiration est obligatoire.")]
    
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateExpiration;

    #[Assert\NotNull(message: "🚨  Le statut est requis.")]
    #[ORM\Column(type: 'string', enumType: Status::class)]
    private ?Status $status = Status::ATTEND;


    public function __construct()
    {
        // Initialiser la date de publication à aujourd'hui par défaut
        $this->datePublication = new \DateTime(); // Définit la date de publication à la date actuelle
        $this->dateExpiration = (new \DateTime())->modify('+1 day'); // Date d'expiration = un jour après la publication
    }



    public function getIdOffre(): ?int
    {
        return $this->idOffre;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
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

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;
        return $this;
    }

    public function getDateExpiration(): ?DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function __toString(): string
    {
        return "Offre: {$this->titre} (" . ($this->status?->value ?? 'Aucun statut') . ")";
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): static
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->add($utilisateur);
            $utilisateur->setOffre($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): static
    {
        if ($this->utilisateurs->removeElement($utilisateur)) {
            // set the owning side to null (unless already changed)
            if ($utilisateur->getOffre() === $this) {
                $utilisateur->setOffre(null);
            }
        }

        return $this;
    }
    
}
