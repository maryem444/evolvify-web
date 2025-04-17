<?php

namespace App\Entity;

use App\Entity\StatusTrajet;
use App\Repository\TrajetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
#[ORM\Table(name: 'trajet')]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id_T;

    #[ORM\Column(type: 'string', length: 255)]
    private string $point_dep;

    #[ORM\Column(type: 'string', length: 255)]
    private string $point_arr;

    #[ORM\Column(type: 'float')]
    private float $distance;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $durée_estimé;

    #[ORM\ManyToOne(targetEntity: MoyenTransport::class, inversedBy: 'trajets')]
    #[ORM\JoinColumn(name: "id_moyen", referencedColumnName: "id_moyen", nullable: false)]
    private ?MoyenTransport $moyenTransport = null;

    #[ORM\Column(type: 'integer')]
    private int $id_employe;

    #[ORM\Column(type: 'string', enumType: StatusTrajet::class)]
    private ?StatusTrajet $status = null;

    // Getter and Setter for id_T
    public function getId_T(): int
    {
        return $this->id_T;
    }

    public function setId_T(int $id_T): self
    {
        $this->id_T = $id_T;
        return $this;
    }

    // Getter and Setter for point_dep
    public function getPoint_dep(): string
    {
        return $this->point_dep;
    }

    public function setPoint_dep(string $point_dep): self
    {
        $this->point_dep = $point_dep;
        return $this;
    }

    // Getter and Setter for point_arr
    public function getPoint_arr(): string
    {
        return $this->point_arr;
    }

    public function setPoint_arr(string $point_arr): self
    {
        $this->point_arr = $point_arr;
        return $this;
    }

    // Getter and Setter for distance
    public function getDistance(): float
    {
        return $this->distance;
    }

    public function setDistance(float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    // Getter and Setter for durée_estimé
    public function getDurée_estimé(): \DateTimeInterface
    {
        return $this->durée_estimé;
    }

    public function setDurée_estimé(\DateTimeInterface $durée_estimé): self
    {
        $this->durée_estimé = $durée_estimé;
        return $this;
    }

    // Getter and Setter for moyenTransport
    public function getMoyenTransport(): ?MoyenTransport
    {
        return $this->moyenTransport;
    }

    public function setMoyenTransport(MoyenTransport $moyenTransport): static
    {
        $this->moyenTransport = $moyenTransport;
        return $this;
    }

    // Getter and Setter for id_employe
    public function getId_employe(): int
    {
        return $this->id_employe;
    }

    public function setId_employe(int $id_employe): self
    {
        $this->id_employe = $id_employe;
        return $this;
    }

    // Getter and Setter for status
    public function getStatus(): ?StatusTrajet
    {
        return $this->status;
    }

    public function setStatus(StatusTrajet $status): static
    {
        $this->status = $status;
        return $this;
    }
}
