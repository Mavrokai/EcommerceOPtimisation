<?php

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThumbnailExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('thumb_url', $this->thumbUrl(...)),
        ];
    }

    /**
     * Génère l'URL d'une miniature pour une image.
     *
     * @param string $imagePath Chemin relatif (ex: "Harnais.webp" ou "categories/chats.jpg")
     */
    public function thumbUrl(string $imagePath, int $width = 150): string
    {
        return $this->urlGenerator->generate('media_thumb', [
            'width' => $width,
            'path' => ltrim($imagePath, '/'),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
