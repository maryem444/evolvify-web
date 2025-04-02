<?php

namespace App\Entity;
use App\Entity\StatusTrajet;
use App\Repository\TrajetRepositoryRepository;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'trajet')]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $idT;

    #[ORM\Column(type: 'string', length: 255)]
    private string $pointDep;

    #[ORM\Column(type: 'string', length: 255)]
    private string $pointArr;

    #[ORM\Column(type: 'float')]
    private float $distance;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $duréeEstimé;

    #[ORM\Column(type: 'integer')]
    private int $idMoyen;

    #[ORM\Column(type: 'integer')]
    private int $idEmploye;

    #[ORM\Column(type: 'string', enumType: StatusTrajet::class)]
    private ?StatusTrajet $status = null;


    #[ORM\ManyToOne(targetEntity: MoyenTransport::class, inversedBy: 'trajets')]
    #[ORM\JoinColumn(name: "idMoyen", referencedColumnName: "idMoyen", nullable: false)]
    private ?MoyenTransport $moyenTransport = null;

    public function getMoyenTransport(): ?MoyenTransport
    {
        return $this->moyenTransport;
    }

    public function setMoyenTransport(?MoyenTransport $moyenTransport): static
    {
        $this->moyenTransport = $moyenTransport;
        return $this;
    }

    // Getter and setter methods for the properties

    public function getIdT(): int
    {
        return $this->idT;
    }

    public function setIdT(int $idT): self
    {
        $this->idT = $idT;

        return $this;
    }

    public function getPointDep(): string
    {
        return $this->pointDep;
    }

    public function setPointDep(string $pointDep): self
    {
        $this->pointDep = $pointDep;

        return $this;
    }

    public function getPointArr(): string
    {
        return $this->pointArr;
    }

    public function setPointArr(string $pointArr): self
    {
        $this->pointArr = $pointArr;

        return $this;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

    public function setDistance(float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getDuréeEstimé(): \DateTimeInterface
    {
        return $this->duréeEstimé;
    }

    public function setDuréeEstimé(\DateTimeInterface $duréeEstimé): self
    {
        $this->duréeEstimé = $duréeEstimé;

        return $this;
    }

    public function getIdMoyen(): int
    {
        return $this->idMoyen;
    }

    public function setIdMoyen(int $idMoyen): self
    {
        $this->idMoyen = $idMoyen;

        return $this;
    }

    public function getIdEmploye(): int
    {
        return $this->idEmploye;
    }

    public function setIdEmploye(int $idEmploye): self
    {
        $this->idEmploye = $idEmploye;

        return $this;
    }

    public function getStatus(): ?StatusTrajet
    {
        return $this->status;
    }

    public function setStatus(StatusTrajet $status): static  // Change type hint to StatusTransport
    {
        $this->status = $status;
        return $this;
    }

}
