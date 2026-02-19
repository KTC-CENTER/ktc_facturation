<?php

namespace App\Form;

use App\Entity\ProformaTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProformaTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du modèle',
                'attr' => [
                    'placeholder' => 'ex: Pack Logiciel Standard',
                    'class' => 'form-input',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description du modèle',
                    'class' => 'form-textarea',
                    'rows' => 2,
                ],
            ])
            ->add('taxRate', NumberType::class, [
                'label' => 'Taux TVA (%)',
                'scale' => 2,
                'attr' => [
                    'placeholder' => '19.25',
                    'class' => 'form-input',
                    'step' => '0.01',
                ],
            ])
            ->add('conditions', TextareaType::class, [
                'label' => 'Conditions',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Conditions par défaut pour ce modèle',
                    'class' => 'form-textarea',
                    'rows' => 4,
                ],
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => TemplateItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Modèle actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-checkbox',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProformaTemplate::class,
        ]);
    }
}
