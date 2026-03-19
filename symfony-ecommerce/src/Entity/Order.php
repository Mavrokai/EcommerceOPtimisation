<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $guestEmail = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'en_attente';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $deliveryMethod = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?PromoCode $promoCode = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', orphanRemoval: true, cascade: ['persist'])]
    private Collection $orderItems;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'order', orphanRemoval: true)]
    private Collection $invoices;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuestEmail(): ?string
    {
        return $this->guestEmail;
    }

    public function setGuestEmail(?string $guestEmail): static
    {
        $this->guestEmail = $guestEmail;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getDeliveryMethod(): ?string
    {
        return $this->deliveryMethod;
    }

    public function setDeliveryMethod(?string $deliveryMethod): static
    {
        $this->deliveryMethod = $deliveryMethod;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPromoCode(): ?PromoCode
    {
        return $this->promoCode;
    }

    public function setPromoCode(?PromoCode $promoCode): static
    {
        $this->promoCode = $promoCode;
        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setOrder($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            if ($invoice->getOrder() === $this) {
                $invoice->setOrder(null);
            }
        }

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->user?->getEmail() ?? $this->guestEmail;
    }

    public function calculateTotalFromItems(): string
    {
        $total = 0;
        foreach ($this->orderItems as $item) {
            $total += (float) $item->getUnitPrice() * $item->getQuantity();
        }
        return (string) $total;
    }

    public function getTotalItemsCount(): int
    {
        $count = 0;
        foreach ($this->orderItems as $item) {
            $count += $item->getQuantity();
        }
        return $count;
    }

    public function __toString(): string
    {
        $result = 'Commande #' . $this->id . ' - ' . $this->getCustomerEmail() . "\n";
        $result .= 'Total: ' . $this->total . '€' . "\n";
        $result .= 'Statut: ' . $this->status . "\n";
        $result .= "Produits commandés:\n";
        
        foreach ($this->orderItems as $item) {
            $result .= '- ' . $item->getDisplayName() . ' x' . $item->getQuantity() . ' = ' . $item->getTotalPrice() . "€\n";
        }
        
        return $result;
    }

    public function getOrderSummary(): string
    {
        $summary = [];
        foreach ($this->orderItems as $item) {
            $summary[] = $item->getDisplayName() . ' (x' . $item->getQuantity() . ')';
        }
        return implode(', ', $summary);
    }

    public function getDetailedOrderInfo(): array
    {
        $details = [];
        foreach ($this->orderItems as $item) {
            $details[] = [
                'product_name' => $item->getDisplayName(),
                'quantity' => $item->getQuantity(),
                'unit_price' => $item->getUnitPrice(),
                'total_price' => $item->getTotalPrice(),
                'product_id' => $item->getProduct()?->getId(),
                'variant_id' => $item->getVariant()?->getId()
            ];
        }
        return $details;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'en_attente' => 'En attente de validation',
            'confirmee' => 'Confirmée',
            'expediee' => 'Expédiée',
            'livree' => 'Livrée',
            'annulee' => 'Annulée',
            default => 'Statut inconnu'
        };
    }

    public function isValidationRequired(): bool
    {
        return $this->status === 'en_attente';
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, ['en_attente', 'confirmee']);
    }
}
