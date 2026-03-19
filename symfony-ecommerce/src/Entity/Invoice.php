<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $pdfUrl = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $generatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    public function __construct()
    {
        $this->generatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPdfUrl(): ?string
    {
        return $this->pdfUrl;
    }

    public function setPdfUrl(?string $pdfUrl): static
    {
        $this->pdfUrl = $pdfUrl;
        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeInterface
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeInterface $generatedAt): static
    {
        $this->generatedAt = $generatedAt;
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

    public function getInvoiceNumber(): string
    {
        return 'FACT-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function __toString(): string
    {
        return $this->getInvoiceNumber() . ' - Commande #' . $this->order?->getId();
    }
}
