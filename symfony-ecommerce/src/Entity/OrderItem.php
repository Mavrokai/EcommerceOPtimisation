<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $unitPrice = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variantName = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    private ?ProductVariant $variant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getVariantName(): ?string
    {
        return $this->variantName;
    }

    public function setVariantName(?string $variantName): static
    {
        $this->variantName = $variantName;
        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getVariant(): ?ProductVariant
    {
        return $this->variant;
    }

    public function setVariant(?ProductVariant $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function getTotalPrice(): string
    {
        return (string) ((float) $this->unitPrice * $this->quantity);
    }

    public function getDisplayName(): string
    {
        $name = $this->productName ?? ($this->product?->getName() ?? 'Produit supprimé');
        
        // Ajouter le variant si disponible
        $variantDisplay = $this->variantName ?? ($this->variant?->getVariantName() ?? null);
        if ($variantDisplay) {
            $name .= ' (' . $variantDisplay . ')';
        }
        
        return $name;
    }

    public function __toString(): string
    {
        return $this->getDisplayName() . ' x' . $this->quantity;
    }

    // Méthode pour initialiser les noms sauvegardés lors de la création de l'OrderItem
    public function saveProductInfo(): static
    {
        if ($this->product) {
            $this->productName = $this->product->getName();
        }
        if ($this->variant) {
            $this->variantName = $this->variant->getVariantName();
        }
        return $this;
    }
}
