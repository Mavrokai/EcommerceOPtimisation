<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si l'utilisateur a confirmé son email
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte n\'est pas encore vérifié. Veuillez vérifier votre email et cliquer sur le lien de confirmation.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Ici on peut ajouter d'autres vérifications post-authentification si nécessaire
    }
}
