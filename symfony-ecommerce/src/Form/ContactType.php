<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre prénom',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre@email.com'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre email',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer un email valide',
                    ]),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '01 23 45 67 89'
                ]
            ])
            ->add('subject', ChoiceType::class, [
                'label' => 'Sujet',
                'choices' => [
                    'Choisir un sujet...' => '',
                    'Question générale' => 'general',
                    'Support technique' => 'support',
                    'Commande et livraison' => 'order',
                    'Retour et remboursement' => 'return',
                    'Partenariat' => 'partnership',
                    'Autre' => 'other'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez choisir un sujet',
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Décrivez votre demande en détail...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre message',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Votre message doit contenir au moins {{ limit }} caractères',
                        'max' => 1000,
                        'maxMessage' => 'Votre message ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}