<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundle;

#[AsEventListener(event: 'kernel.request', priority: 10)]
class AdminAccessListener
{
    public function __construct(
        private SecurityBundle $security,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        // Ignorer les sous-requêtes internes (gestion d'erreurs, ESI, fragments)
        if (!$event->isMainRequest()) {
            return;
        }

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
            // setResponse() court-circuite tout le cycle kernel (controller, arguments, view)
            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate('admin_access_denied')
            ));
        }
    }
}
