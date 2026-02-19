<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Invoice;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'label' => 'Client',
                'placeholder' => 'Sélectionner un client',
                'attr' => [
                    'class' => 'form-select',
                ],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('c')
                        ->where('c.isArchived = false')
                        ->orderBy('c.name', 'ASC');
                },
            ])
            ->add('object', TextType::class, [
                'label' => 'Objet',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Objet de la facture',
                    'class' => 'form-input',
                ],
            ])
            ->add('issueDate', DateType::class, [
                'label' => 'Date d\'émission',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input',
                ],
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Date d\'échéance',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input',
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
            ->add('items', CollectionType::class, [
                'entry_type' => DocumentItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Notes visibles sur le document',
                    'class' => 'form-textarea',
                    'rows' => 3,
                ],
            ])
            ->add('conditions', TextareaType::class, [
                'label' => 'Conditions de paiement',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Conditions de paiement',
                    'class' => 'form-textarea',
                    'rows' => 4,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}
