<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DonationRepository")
 */
class Donation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="donations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="float")
     */
    private $requested_quantity;

    /**
     * @ORM\Column(type="float", options={"default": 0}, nullable=true))
     */
    private $existing_quantity = 0;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hospital;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $blood_type;

    /**
     * @ORM\Column(type="date")
     */
    private $creation_date;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $last_donation_date;

    /**
     * @ORM\Column(type="integer", options={"default": 0}, nullable=true)
     */
    private $donations_count = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRequestedQuantity(): ?float
    {
        return $this->requested_quantity;
    }

    public function setRequestedQuantity(float $requested_quantity): self
    {
        $this->requested_quantity = $requested_quantity;

        return $this;
    }

    public function getExistingQuantity(): ?float
    {
        return $this->existing_quantity;
    }

    public function setExistingQuantity(float $existing_quantity): self
    {
        $this->existing_quantity = $existing_quantity;

        return $this;
    }

    public function getHospital(): ?string
    {
        return $this->hospital;
    }

    public function setHospital(string $hospital): self
    {
        $this->hospital = $hospital;

        return $this;
    }

    public function getBloodType(): ?string
    {
        return $this->blood_type;
    }

    public function setBloodType(string $blood_type): self
    {
        $this->blood_type = $blood_type;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creation_date;
    }

    public function setCreationDate(\DateTimeInterface $creation_date): self
    {
        $this->creation_date = $creation_date;

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

    public function getDonationsCount(): ?int
    {
        return $this->donations_count;
    }

    public function setDonationsCount(?int $donations_count): self
    {
        $this->donations_count = $donations_count;

        return $this;
    }
}
