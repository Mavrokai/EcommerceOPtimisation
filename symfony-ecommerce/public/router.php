<?php

/**
 * Routeur pour le serveur PHP intégré.
 * Sert les assets avec Cache-Control pour un cache long.
 *
 * Utilisation : php -S 127.0.0.1:8000 -t public public/router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = urldecode($path);

// Sécurité : bloquer les chemins avec ..
if (str_contains($path, '..')) {
    return false;
}

$file = __DIR__ . $path;

$cacheableExtensions = [
    'ico' => 'image/x-icon',
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'css' => 'text/css',
    'js' => 'application/javascript',
];

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if (is_file($file) && isset($cacheableExtensions[$ext])) {
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Type: ' . $cacheableExtensions[$ext]);

    $content = file_get_contents($file);
    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

    if (in_array($ext, ['css', 'js']) && str_contains($acceptEncoding, 'gzip') && function_exists('gzencode')) {
        $compressed = gzencode($content, 6);
        if ($compressed !== false && strlen($compressed) < strlen($content)) {
            header('Content-Encoding: gzip');
            header('Content-Length: ' . strlen($compressed));
            header('Vary: Accept-Encoding');
            echo $compressed;
            return true;
        }
    }

    header('Content-Length: ' . strlen($content));
    echo $content;
    return true;
}

// Fichier physique existant : laisser le serveur le servir (ex: index.php)
if (is_file($file)) {
    return false;
}

// Route dynamique : passer à Symfony (front controller)
chdir(__DIR__);
require __DIR__ . '/index.php';
return true;
