<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

trait AdminSecurityTrait
{
    protected function checkAdminAccess(): void
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à esta page.');
        }
        
        if (!$user->isAdmin()) {
            throw new AccessDeniedException('Accès refusé. Vous devez être administrateur pour accéder à cette page.');
        }
    }
}
