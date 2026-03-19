<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\SubCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'placeholder' => 'Ex: Croquettes Premium pour chien',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom du produit est obligatoire'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez ce produit...',
                    'class' => 'form-control',
                    'rows' => 5
                ],
                'constraints' => [
                    new Length([
                        'max' => 2000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (€)',
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le prix est obligatoire'
                    ]),
                    new Positive([
                        'message' => 'Le prix doit être positif'
                    ])
                ]
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le stock est obligatoire'
                    ]),
                    new PositiveOrZero([
                        'message' => 'Le stock doit être positif ou nul'
                    ])
                ]
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Poids (kg)',
                'required' => false,
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new PositiveOrZero([
                        'message' => 'Le poids doit être positif ou nul'
                    ])
                ]
            ])
            ->add('imageUrl', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WebP, GIF)'
                    ])
                ]
            ])
            ->add('subCategory', EntityType::class, [
                'class' => SubCategory::class,
                'choice_label' => function(SubCategory $subCategory) {
                    return $subCategory->getCategory()->getName() . ' > ' . $subCategory->getName();
                },
                'label' => 'Catégorie > Sous-catégorie',
                'placeholder' => 'Choisir une catégorie et sous-catégorie',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La sous-catégorie est obligatoire'
                    ])
                ]
            ])
            ->add('isPromo', CheckboxType::class, [
                'label' => 'Produit en promotion',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('promoPercentage', NumberType::class, [
                'label' => 'Pourcentage de promotion (%)',
                'required' => false,
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '100'
                ],
                'constraints' => [
                    new PositiveOrZero([
                        'message' => 'Le pourcentage doit être positif ou nul'
                    ])
                ],
                'help' => 'Pourcentage de réduction (0-100). Ne s\'applique que si le produit est en promotion.'
            ])
            ->add('isNew', CheckboxType::class, [
                'label' => 'Nouveau produit',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
