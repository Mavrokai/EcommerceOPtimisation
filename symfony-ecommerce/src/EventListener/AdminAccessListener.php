<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundle;

#[AsEventListener(event: 'kernel.request', priority: 10)]
class AdminAccessListener
{
    public function __construct(
        private SecurityBundle $security,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function __invoke(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Vérifier si la route commence par /admin (mais pas /admin-access-denied)
        if (!str_starts_with($pathInfo, '/admin') || $pathInfo === '/admin-access-denied') {
            return;
        }

        // Récupérer l'utilisateur connecté
        /** @var User|null $user */
        $user = $this->security->getUser();

        // Vérifier si l'utilisateur est connecté et admin
        if (!$user || !$user->isAdmin()) {
            // Rediriger vers la page d'erreur personnalisée
            $response = new RedirectResponse($this->urlGenerator->generate('admin_access_denied'));
            $event->setController(function() use ($response) {
                return $response;
            });
        }
    }
}
