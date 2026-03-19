<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class WebpExtension extends AbstractExtension
{
    public function __construct(private readonly string $projectDir) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('webp_path', $this->toWebpPath(...)),
        ];
    }

    /**
     * Converts an asset-relative path to its WebP equivalent if it exists.
     * Example: 'assets/images/foo.jpg' → 'assets/images/foo.webp'
     * Falls back to the original path when no WebP version is found.
     */
    public function toWebpPath(string $assetPath): string
    {
        $ext = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return $assetPath;
        }

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $assetPath);
        $absoluteWebp = $this->projectDir . '/public/' . ltrim($webpPath, '/');

        return file_exists($absoluteWebp) ? $webpPath : $assetPath;
    }
}
