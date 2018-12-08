<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=190, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=190, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $blood_type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $hospital;

    /**
     * @ORM\Column(type="boolean", options={"default": 1})
     */
    private $is_valid = TRUE;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $last_donation_date;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jwt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Donation", mappedBy="user", orphanRemoval=true)
     */
    private $donations;

    public function __construct()
    {
        $this->donations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getBloodType(): ?string
    {
        return $this->blood_type;
    }

    public function setBloodType(?string $blood_type): self
    {
        $this->blood_type = $blood_type;

        return $this;
    }

    public function getHospital(): ?string
    {
        return $this->hospital;
    }

    public function setHospital(?string $hospital): self
    {
        $this->hospital = $hospital;

        return $this;
    }

    public function getIsValid(): ?bool
    {
        return $this->is_valid;
    }

    public function setIsValid(bool $is_valid): self
    {
        $this->is_valid = $is_valid;

        return $this;
    }

    public function getLastDonationDate(): ?\DateTimeInterface
    {
        return $this->last_donation_date;
    }

    public function setLastDonationDate(?\DateTimeInterface $last_donation_date): self
    {
        $this->last_donation_date = $last_donation_date;

        return $this;
    }

    public function getJwt(): ?string
    {
        return $this->jwt;
    }

    public function setJwt(?string $jwt): self
    {
        $this->jwt = $jwt;

        return $this;
    }

    public function getUserTypeInt() {
        switch ($this->type) {
            case 'admin':
                return 1;
            case 'centre':
                return 2;
            case 'doctor':
                return 3;
            case 'donor':
                return 4;
            default:
                return -1;
        }
    }

    /**
     * @return Collection|Donation[]
     */
    public function getDonations(): Collection
    {
        return $this->donations;
    }

    public function addDonation(Donation $donation): self
    {
        if (!$this->donations->contains($donation)) {
            $this->donations[] = $donation;
            $donation->setUser($this);
        }

        return $this;
    }

    public function removeDonation(Donation $donation): self
    {
        if ($this->donations->contains($donation)) {
            $this->donations->removeElement($donation);
            // set the owning side to null (unless already changed)
            if ($donation->getUser() === $this) {
                $donation->setUser(null);
            }
        }

        return $this;
    }
}
