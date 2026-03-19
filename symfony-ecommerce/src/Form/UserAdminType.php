<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class UserAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['data']->getId() !== null;

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@domain.com'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'adresse email est obligatoire',
                    ]),
                    new Email([
                        'message' => 'L\'adresse email n\'est pas valide',
                    ]),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prénom'
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de famille'
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => !$isEdit, // Obligatoire pour la création, facultatif pour la modification
                'first_options' => [
                    'label' => $isEdit ? 'Nouveau mot de passe' : 'Mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => $isEdit ? 'Laisser vide pour ne pas modifier' : 'Mot de passe'
                    ],
                    'help' => $isEdit ? 'Laisser vide pour conserver le mot de passe actuel' : null,
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Confirmer le mot de passe'
                    ],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => $isEdit ? [] : [
                    new NotBlank([
                        'message' => 'Le mot de passe est obligatoire',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('isAdmin', CheckboxType::class, [
                'label' => 'Administrateur',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
                'help' => 'Cocher cette case pour donner les droits d\'administrateur à cet utilisateur',
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'Compte vérifié',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
                'help' => 'Cocher cette case si le compte email de l\'utilisateur est vérifié',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
