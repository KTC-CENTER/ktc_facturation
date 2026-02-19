<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom / Raison sociale',
                'attr' => [
                    'placeholder' => 'Nom du client ou de l\'entreprise',
                    'class' => 'form-input',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'email@exemple.com',
                    'class' => 'form-input',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => '+237 6XX XXX XXX',
                    'class' => 'form-input',
                ],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Adresse complète',
                    'class' => 'form-textarea',
                    'rows' => 3,
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ville',
                    'class' => 'form-input',
                ],
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'data' => $options['data']->getCountry() ?? 'Cameroun',
                'attr' => [
                    'placeholder' => 'Pays',
                    'class' => 'form-input',
                ],
            ])
            ->add('taxId', TextType::class, [
                'label' => 'N° Contribuable',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Numéro d\'identification fiscale',
                    'class' => 'form-input',
                ],
            ])
            ->add('rccm', TextType::class, [
                'label' => 'RCCM',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Registre du commerce',
                    'class' => 'form-input',
                ],
            ])
            ->add('contactPerson', TextType::class, [
                'label' => 'Personne de contact',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom du contact principal',
                    'class' => 'form-input',
                ],
            ])
            ->add('contactPhone', TelType::class, [
                'label' => 'Téléphone du contact',
                'required' => false,
                'attr' => [
                    'placeholder' => '+237 6XX XXX XXX',
                    'class' => 'form-input',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Notes internes sur ce client',
                    'class' => 'form-textarea',
                    'rows' => 3,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
