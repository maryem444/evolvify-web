<?php
namespace App\Entity;

use App\Entity\StatusTransport;
use App\Entity\Trajet;
use App\Repository\MoyenTransportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MoyenTransportRepository::class)]
#[ORM\Table(name: "moyentransport")]  // Spécifie le nom de la table dans la base de données
class MoyenTransport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_moyen", type: "integer")]
    private ?int $id_moyen = null;

    #[ORM\Column(length: 255)]
    private ?string $type_moyen = null;

    #[ORM\Column]
    private ?int $capacité = null;

    #[ORM\Column(type: 'integer')]
    private ?int $immatriculation = null;

    #[ORM\Column(type: 'string', enumType: StatusTransport::class)]
    private ?StatusTransport $status = null;
    
    #[ORM\OneToMany(targetEntity: Trajet::class, mappedBy: 'moyenTransport')]
    private Collection $trajets;
    
    public function __construct()
    {
        $this->trajets = new ArrayCollection();
    }

    public function getId_moyen(): ?int
    {
        return $this->id_moyen;
    }

    public function getType_moyen(): ?string
    {
        return $this->type_moyen;
    }

    public function setType_moyen(string $type_moyen): static
    {
        $this->type_moyen = $type_moyen;
        return $this;
    }

    public function getCapacité(): ?int
    {
        return $this->capacité;
    }

    public function setCapacité(int $capacité): static
    {
        $this->capacité = $capacité;
        return $this;
    }

    public function getImmatriculation(): ?int
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(int $immatriculation): static
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    public function getStatus(): ?StatusTransport
    {
        return $this->status;
    }

    public function setStatus(StatusTransport $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return Collection<int, Trajet>
     */
    public function getTrajets(): Collection
    {
        return $this->trajets;
    }

    public function addTrajet(Trajet $trajet): self
    {
        if (!$this->trajets->contains($trajet)) {
            $this->trajets->add($trajet);
            $trajet->setMoyenTransport($this);
        }
        
        return $this;
    }

    public function removeTrajet(Trajet $trajet): self
    {
        if ($this->trajets->removeElement($trajet)) {
            // set the owning side to null (unless already changed)
            if ($trajet->getMoyenTransport() === $this) {
                //$trajet->setMoyenTransport(null);
            }
        }
        
        return $this;
    }
  
    public function __toString(): string
    {
        return $this->type_moyen . ' (' . $this->immatriculation . ')';
    }
}
