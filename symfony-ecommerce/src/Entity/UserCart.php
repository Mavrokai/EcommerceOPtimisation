<?php

namespace App\Entity;

use App\Repository\UserCartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCartRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserCart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'userCarts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(targetEntity: CartItem::class, mappedBy: 'userCart', orphanRemoval: true)]
    private Collection $cartItems;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
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
            $cartItem->setUserCart($this);
        }

        return $this;
    }

    public function removeCartItem(CartItem $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem)) {
            // set the owning side to null (unless already changed)
            if ($cartItem->getUserCart() === $this) {
                $cartItem->setUserCart(null);
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
        return 'Panier de ' . $this->user?->getEmail() ?? 'Utilisateur inconnu';
    }
}
