<?php
namespace App\Entity;

use App\Entity\StatusAbonnement;
use App\Repository\AbonnementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
#[ORM\Table(name: "abonnement")]  
class Abonnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_Ab", type: "integer")]
    private ?int $id_Ab = null;

    #[ORM\Column(name: "type_Ab", length: 255)]
    private ?string $type_Ab = null;

    #[ORM\Column(name: "date_debut", type: "date")]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(name: "date_exp", type: "date")]
    private ?\DateTimeInterface $date_exp = null;

    #[ORM\Column(name: "prix", type: "float")]
    private ?float $prix = null;

    #[ORM\Column(name: "id_employe", type: "integer")]
    private ?int $id_employe = null;

    #[ORM\Column(name: "status", type: "string", enumType: StatusAbonnement::class)]
    private ?StatusAbonnement $status = null;

    public function __construct()
    {
        // Set default value for date_debut to the current date without the time
        $this->date_debut = new \DateTime();
        //$this->date_debut->setTime(0, 0, 0); // Ensure only the date is set (no time)
    }

    public function getId_Ab(): ?int
    {
        return $this->id_Ab;
    }

    public function getType_Ab(): ?string
    {
        return $this->type_Ab;
    }

    public function setType_Ab(string $type_Ab): self
    {
        $this->type_Ab = $type_Ab;

        return $this;
    }

    public function getDate_debut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDate_debut(\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDate_exp(): ?\DateTimeInterface
    {
        return $this->date_exp;
    }

    public function setDate_exp(\DateTimeInterface $date_exp): self
    {
        $this->date_exp = $date_exp;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getId_employe(): ?int
    {
        return $this->id_employe;
    }

    public function setId_employe(int $id_employe): self
    {
        $this->id_employe = $id_employe;

        return $this;
    }

    public function getStatus(): ?StatusAbonnement
    {
        return $this->status;
    }

    public function setStatus(StatusAbonnement $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function __toString(): string
    {
        return 'Abonnement{'.
               'id_Ab='.$this->id_Ab.
               ", type_Ab='".$this->type_Ab.'\''.
               ', date_debut='.($this->date_debut ? $this->date_debut->format('Y-m-d') : 'null').
               ', date_exp='.($this->date_exp ? $this->date_exp->format('Y-m-d') : 'null').
               ', prix='.$this->prix.
               ', id_employe='.$this->id_employe.
               ', status='.$this->status.
               '}';
    }
}
