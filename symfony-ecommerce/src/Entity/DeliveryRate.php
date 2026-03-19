<?php

namespace App\Entity;

use App\Repository\DeliveryRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeliveryRateRepository::class)]
class DeliveryRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $method = null;

    #[ORM\Column(length: 100)]
    private ?string $country = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $basePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $pricePerKg = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getBasePrice(): ?string
    {
        return $this->basePrice;
    }

    public function setBasePrice(string $basePrice): static
    {
        $this->basePrice = $basePrice;
        return $this;
    }

    public function getPricePerKg(): ?string
    {
        return $this->pricePerKg;
    }

    public function setPricePerKg(string $pricePerKg): static
    {
        $this->pricePerKg = $pricePerKg;
        return $this;
    }

    public function calculateDeliveryPrice(float $totalWeight): string
    {
        $base = (float) $this->basePrice;
        $perKg = (float) $this->pricePerKg;
        $total = $base + ($perKg * $totalWeight);
        
        return (string) $total;
    }

    public function __toString(): string
    {
        return $this->method . ' - ' . $this->country;
    }
}
