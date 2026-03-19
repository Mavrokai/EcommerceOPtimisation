<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -256)]
class CompressionListener
{
    private const COMPRESSIBLE_TYPES = [
        'text/html',
        'text/css',
        'text/javascript',
        'application/javascript',
        'application/json',
        'application/xml',
        'text/xml',
        'text/plain',
    ];

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($response->headers->has('Content-Encoding')) {
            return;
        }

        $acceptEncoding = $request->headers->get('Accept-Encoding', '');
        if (!str_contains(strtolower($acceptEncoding), 'gzip')) {
            return;
        }

        $contentType = $response->headers->get('Content-Type', '');
        $isCompressible = false;
        foreach (self::COMPRESSIBLE_TYPES as $type) {
            if (str_starts_with($contentType, $type)) {
                $isCompressible = true;
                break;
            }
        }

        if (!$isCompressible) {
            return;
        }

        $content = $response->getContent();
        if ($content === false || $content === '' || strlen($content) < 256) {
            return;
        }

        if (!function_exists('gzencode')) {
            return;
        }

        $compressed = gzencode($content, 6);
        if ($compressed === false || strlen($compressed) >= strlen($content)) {
            return;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding', false);
    }
}
