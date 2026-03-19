<?php
namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('homepage');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            $errorMessage = $error->getMessageKey();
            
            if ($errorMessage === 'Invalid credentials.') {
                $this->addFlash('error', 'Email ou mot de passe incorrect. Veuillez vérifier vos informations.');
            } elseif ($errorMessage === 'Username could not be found.') {
                $this->addFlash('error', 'Aucun compte trouvé avec cet email. Vérifiez votre adresse ou inscrivez-vous.');
            } else {
                $this->addFlash('error', 'Erreur de connexion. Veuillez réessayer.');
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
                throw new \Exception('This should never be reached!');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('homepage');
        }

        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Supprimer les anciens tokens pour cet utilisateur
                $oldTokens = $entityManager->getRepository(PasswordResetToken::class)
                    ->findBy(['user' => $user, 'isUsed' => false]);
                
                foreach ($oldTokens as $token) {
                    $entityManager->remove($token);
                }

                // Créer un nouveau token
                $resetToken = new PasswordResetToken();
                $resetToken->setUser($user);
                
                $entityManager->persist($resetToken);
                $entityManager->flush();
                $resetUrl = $this->generateUrl('app_reset_password', [
                    'token' => $resetToken->getToken()
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                // Envoyer l'email de réinitialisation
                try {
                    $emailMessage = (new Email())
                        ->from('noreply@clochette.co')
                        ->to($user->getEmail())
                        ->subject('🐾 Réinitialisation de votre mot de passe - Clochette&CO')
                        ->html($this->renderView('emails/reset_password.html.twig', [
                            'user' => $user,
                            'resetUrl' => $resetUrl,
                            'token' => $resetToken
                        ]));

                    $mailer->send($emailMessage);
                    
                } catch (\Exception $e) {
                    // En cas d'erreur d'envoi, log l'erreur mais ne pas révéler à l'utilisateur
                    error_log('Erreur envoi email reset password: ' . $e->getMessage());
                    $this->addFlash('warning', 'Problème technique détecté. Veuillez réessayer dans quelques minutes.');
                }
            }

            // Message générique pour la sécurité
            $this->addFlash('success', ' Si cette adresse email existe dans notre système, un lien de réinitialisation a été envoyé.');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/forgot_password.html.twig', [
            'forgotPasswordForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        PasswordResetTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $resetToken = $tokenRepository->findValidTokenByToken($token);

        if (!$resetToken) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $user = $resetToken->getUser();

            // Hasher le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // Marquer le token comme utilisé
            $resetToken->setIsUsed(true);

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetPasswordForm' => $form->createView(),
            'token' => $token,
        ]);
    }
}


