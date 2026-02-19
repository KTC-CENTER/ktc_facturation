<?php

namespace App\Form;

use App\Entity\EmailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du modèle',
                'attr' => [
                    'placeholder' => 'ex: Envoi proforma, Relance facture',
                    'class' => 'form-input',
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Sujet de l\'email',
                'attr' => [
                    'placeholder' => 'ex: Votre proforma {reference}',
                    'class' => 'form-input',
                ],
                'help' => 'Variables disponibles: {reference}, {client_name}, {total}, {date}',
            ])
            ->add('bodyHtml', TextareaType::class, [
                'label' => 'Corps de l\'email',
                'attr' => [
                    'placeholder' => 'Contenu de l\'email...',
                    'class' => 'form-textarea',
                    'rows' => 12,
                ],
                'help' => 'Variables: {reference}, {client_name}, {total}, {date}, {due_date}, {link}',
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
            'data_class' => EmailTemplate::class,
        ]);
    }
}
