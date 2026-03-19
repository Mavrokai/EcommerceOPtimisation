<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    private ?ProductVariant $variant = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    private ?UserCart $userCart = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    private ?GuestCart $guestCart = null;

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

    public function getUserCart(): ?UserCart
    {
        return $this->userCart;
    }

    public function setUserCart(?UserCart $userCart): static
    {
        $this->userCart = $userCart;
        return $this;
    }

    public function getGuestCart(): ?GuestCart
    {
        return $this->guestCart;
    }

    public function setGuestCart(?GuestCart $guestCart): static
    {
        $this->guestCart = $guestCart;
        return $this;
    }

    public function getUnitPrice(): string
    {
        if ($this->variant && $this->variant->getPrice()) {
            return $this->variant->getPrice();
        }
        return $this->product?->getPrice() ?? '0';
    }

    public function getTotalPrice(): string
    {
        return (string) ((float) $this->getUnitPrice() * $this->quantity);
    }

    public function getProductName(): string
    {
        $name = $this->product?->getName() ?? 'Produit supprimé';
        if ($this->variant) {
            $name .= ' (' . $this->variant->getVariantName() . ')';
        }
        return $name;
    }

    public function __toString(): string
    {
        return $this->getProductName() . ' x' . $this->quantity;
    }
}
