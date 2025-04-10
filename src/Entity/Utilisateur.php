<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Genre;
use App\Entity\Role;
#[ORM\Entity]
#[ORM\Table(name: "users")]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_employe = null;

    #[ORM\Column(length: 50)]
    private ?string $firstname = null;

    #[ORM\Column(length: 50)]
    private ?string $lastname = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $num_tel = null;

    #[ORM\Column(name: "birthdayDate", type: "date", nullable: true)]
    private ?\DateTimeInterface $birthdayDate = null;

    #[ORM\Column(name: "joiningDate", type: "date", nullable: true)]
    private ?\DateTimeInterface $joiningDate = null;

    #[ORM\Column(name: "profilePhoto" ,length: 255, nullable: true)]
    private ?string $profilePhoto = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $uploaded_cv = null;

    #[ORM\Column(type: "integer")]
    private ?int $tt_restants = 0;

    #[ORM\Column(type: "integer")]
    private ?int $conge_restant = 0;

    #[ORM\Column(type: 'string', length: 20, enumType: Role::class)]
    private ?Role $role ;

    #[ORM\Column(type: 'string', length: 10, enumType: Genre::class)]
    private ?Genre $gender = Genre::HOMME; 

    #[ORM\Column(type: "boolean")]
    private bool $firstLogin = true;



    public function getIdEmploye(): ?int
    {
        return $this->id_employe;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getNumTel(): ?string
    {
        return $this->num_tel;
    }

    public function setNumTel(string $num_tel): self
    {
        $this->num_tel = $num_tel;
        return $this;
    }

    public function getBirthdayDate(): ?\DateTimeInterface
    {
        return $this->birthdayDate;
    }

    public function setBirthdayDate(?\DateTimeInterface $birthdayDate): self
    {
        $this->birthdayDate = $birthdayDate;
        return $this;
    }

    public function getJoiningDate(): ?\DateTimeInterface
    {
        return $this->joiningDate;
    }

    public function setJoiningDate(?\DateTimeInterface $joiningDate): self
    {
        $this->joiningDate = $joiningDate;
        return $this;
    }

    public function getProfilePhoto(): ?string
    {
        return $this->profilePhoto;
    }

    public function setProfilePhoto(?string $profilePhoto): self
    {
        $this->profilePhoto = $profilePhoto;
        return $this;
    }

    public function getUploadedCv(): ?string
    {
        return $this->uploaded_cv;
    }

    public function setUploadedCv(?string $uploaded_cv): self
    {
        $this->uploaded_cv = $uploaded_cv;
        return $this;
    }

    public function getTtRestants(): int
    {
        return $this->tt_restants;
    }

    public function setTtRestants(int $tt_restants): self
    {
        $this->tt_restants = $tt_restants;
        return $this;
    }

    public function getCongeRestant(): int
    {
        return $this->conge_restant;
    }

    public function setCongeRestant(int $conge_restant): self
    {
        $this->conge_restant = $conge_restant;
        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getGender(): ?Genre
    {
        return $this->gender;
    }

    public function setGender(Genre $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function isFirstLogin(): bool
    {
        return $this->firstLogin;
    }

    public function setFirstLogin(bool $firstLogin): self
    {
        $this->firstLogin = $firstLogin;
        return $this;
    }

    public function getOffre(): ?Offre
    {
        return $this->Offre;
    }

    public function setOffre(?Offre $Offre): static
    {
        $this->Offre = $Offre;

        return $this;
    }
}
