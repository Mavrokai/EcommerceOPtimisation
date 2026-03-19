<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Order;
use App\Form\RegistrationFormType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée. Veuillez utiliser une autre adresse ou vous connecter.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $user->setIsVerified(false);
            $user->setConfirmationToken(Uuid::v4()->toRfc4122());

            try {
                // Sauvegarde bd
                $entityManager->persist($user);
                $entityManager->flush();

                $confirmationUrl = $urlGenerator->generate('app_verify_email', [
                    'token' => $user->getConfirmationToken(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $email = (new Email())
                    ->from('rigelcodjia@gmail.com') 
                    ->to($user->getEmail())
                    ->subject('Confirmez votre adresse email')
                    ->html("<p>Merci de vous être inscrit ! Cliquez <a href='$confirmationUrl'>ici</a> pour confirmer votre email.</p>");

                $mailer->send($email);
                $this->addFlash('success', 'Un email de confirmation vient de vous être envoyé.');

                return $this->redirectToRoute('app_login');
                
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée. Veuillez utiliser une autre adresse ou vous connecter.');
            } catch (\Exception $e) {
                // Gestion des autres erreurs
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.');
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Token de confirmation invalide.');
            return $this->redirectToRoute('app_register');
        }

        $user->setIsVerified(true);
        $user->setConfirmationToken(null);
        
        $linkedOrdersCount = $entityManager->getRepository(Order::class)->linkGuestOrdersToUser($user);
        
        $entityManager->flush();

        if ($linkedOrdersCount > 0) {
            $this->addFlash('success', sprintf(
                'Ton email a bien été confirmé. Tu peux maintenant te connecter. %d commande(s) précédente(s) ont été associée(s) à ton compte.',
                $linkedOrdersCount
            ));
        } else {
            $this->addFlash('success', 'Ton email a bien été confirmé. Tu peux maintenant te connecter.');
        }

        return $this->redirectToRoute('app_login');
    }
}