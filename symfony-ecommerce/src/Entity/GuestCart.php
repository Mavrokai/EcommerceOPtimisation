<?php

namespace App\Entity;

use App\Repository\GuestCartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GuestCartRepository::class)]
#[ORM\HasLifecycleCallbacks]
class GuestCart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $guestToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(targetEntity: CartItem::class, mappedBy: 'guestCart', orphanRemoval: true)]
    private Collection $cartItems;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->guestToken = uniqid('guest_', true);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuestToken(): ?string
    {
        return $this->guestToken;
    }

    public function setGuestToken(string $guestToken): static
    {
        $this->guestToken = $guestToken;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItem $cartItem): static
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setGuestCart($this);
        }

        return $this;
    }

    public function removeCartItem(CartItem $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem)) {
            // set the owning side to null (unless already changed)
            if ($cartItem->getGuestCart() === $this) {
                $cartItem->setGuestCart(null);
            }
        }

        return $this;
    }

    public function getTotalPrice(): string
    {
        $total = 0;
        foreach ($this->cartItems as $item) {
            $total += (float) $item->getTotalPrice();
        }
        return (string) $total;
    }

    public function getTotalItemsCount(): int
    {
        $count = 0;
        foreach ($this->cartItems as $item) {
            $count += $item->getQuantity();
        }
        return $count;
    }

    public function isEmpty(): bool
    {
        return $this->cartItems->isEmpty();
    }

    public function clear(): void
    {
        $this->cartItems->clear();
    }

    public function __toString(): string
    {
        return 'Panier invité ' . $this->guestToken;
    }
}
