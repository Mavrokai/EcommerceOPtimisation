<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        
        if ($this->getUser()) {
            $user = $this->getUser();
            $form->get('firstName')->setData($user->getFirstName());
            $form->get('lastName')->setData($user->getLastName());
            $form->get('email')->setData($user->getEmail());
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            try {
                // Création de l'email
                $email = (new Email())
                    ->from($data['email'])
                    ->to('lahouazi.mk@gmail.com') 
                    ->subject('Nouveau message de contact : ' . $data['subject'])
                    ->html($this->renderView('emails/contact.html.twig', [
                        'data' => $data
                    ]));

                // Envoi de l'email
                $mailer->send($email);
                
                // Email de confirmation à l'expéditeur
                $confirmationEmail = (new Email())
                    ->from('lahouazi.mk@gmail.com')
                    ->to($data['email'])
                    ->subject('Confirmation de réception de votre message')
                    ->html($this->renderView('emails/contact_confirmation.html.twig', [
                        'data' => $data
                    ]));

                $mailer->send($confirmationEmail);

                $this->addFlash('success', 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.');
                
                return $this->redirectToRoute('app_contact');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer.');
            }
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}