<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('line1', null, [
                'label' => 'Adresse (ligne 1)'
            ])
            ->add('line2', null, [
                'label' => 'Complément d\'adresse (ligne 2)',
                'required' => false
            ])
            ->add('city', null, [
                'label' => 'Ville'
            ])
            ->add('postalCode', null, [
                'label' => 'Code postal'
            ])
            ->add('country', null, [
                'label' => 'Pays'
            ])
            ->add('isDefault', null, [
                'label' => 'Adresse principale',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
