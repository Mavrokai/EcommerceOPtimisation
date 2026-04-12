<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    public function __construct(
        private readonly string $projectDir
    ) {}

    #[Route('/media/thumb/{width}/{path}', name: 'media_thumb', requirements: ['path' => '.+', 'width' => '\d+'])]
    public function thumb(int $width, string $path): Response
    {
        $width = min(max($width, 50), 800);
        $path = str_replace(['../', '..'], '', $path);
        $baseDir = $this->projectDir . '/public/assets/images/';
        $sourcePath = realpath($baseDir . $path);

        if (!$sourcePath || !str_starts_with($sourcePath, realpath($baseDir)) || !is_file($sourcePath)) {
            throw new NotFoundHttpException('Image introuvable');
        }

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new NotFoundHttpException('Format non supporté');
        }

        $cacheDir = $this->projectDir . '/var/cache/media/' . $width . '/';
        $cachePath = $cacheDir . md5($path) . '.' . $ext;

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        if (!file_exists($cachePath) || filemtime($cachePath) < filemtime($sourcePath)) {
            $this->createThumbnail($sourcePath, $cachePath, $width, $ext);
        }

        $response = new BinaryFileResponse($cachePath);
        $response->headers->set('Content-Type', $this->getMimeType($ext));
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');

        return $response;
    }

    private function createThumbnail(string $source, string $dest, int $maxWidth, string $ext): void
    {
        $image = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($source),
            'png' => imagecreatefrompng($source),
            'gif' => imagecreatefromgif($source),
            'webp' => imagecreatefromwebp($source),
            default => null,
        };

        if (!$image) {
            copy($source, $dest);
            return;
        }

        $origW = imagesx($image);
        $origH = imagesy($image);

        if ($origW <= $maxWidth) {
            imagedestroy($image);
            copy($source, $dest);
            return;
        }

        $ratio = $maxWidth / $origW;
        $newW = $maxWidth;
        $newH = (int) round($origH * $ratio);

        $thumb = imagecreatetruecolor($newW, $newH);
        if (!$thumb) {
            imagedestroy($image);
            copy($source, $dest);
            return;
        }

        if ($ext === 'png' || $ext === 'gif') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $saved = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($thumb, $dest, 85),
            'png' => imagepng($thumb, $dest, 8),
            'gif' => imagegif($thumb, $dest),
            'webp' => imagewebp($thumb, $dest, 85),
            default => false,
        };

        imagedestroy($image);
        imagedestroy($thumb);

        if (!$saved) {
            copy($source, $dest);
        }
    }

    private function getMimeType(string $ext): string
    {
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
