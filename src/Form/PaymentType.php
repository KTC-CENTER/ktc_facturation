<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxAmount = $options['max_amount'] ?? null;

        $constraints = [
            new NotBlank([
                'message' => 'Veuillez entrer un montant',
            ]),
            new GreaterThan([
                'value' => 0,
                'message' => 'Le montant doit être supérieur à 0',
            ]),
        ];

        if ($maxAmount !== null) {
            $constraints[] = new LessThanOrEqual([
                'value' => $maxAmount,
                'message' => 'Le montant ne peut pas dépasser {{ compared_value }} FCFA',
            ]);
        }

        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Montant du paiement',
                'currency' => 'XAF',
                'divisor' => 1,
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-input',
                    'max' => $maxAmount,
                ],
                'constraints' => $constraints,
            ])
            ->add('paymentDate', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'data' => new \DateTime(),
                'attr' => [
                    'class' => 'form-input',
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Espèces' => 'cash',
                    'Virement bancaire' => 'bank_transfer',
                    'Chèque' => 'check',
                    'Mobile Money' => 'mobile_money',
                    'Orange Money' => 'orange_money',
                    'MTN Mobile Money' => 'mtn_momo',
                    'Carte bancaire' => 'card',
                    'Autre' => 'other',
                ],
                'placeholder' => 'Sélectionner un mode de paiement',
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un mode de paiement',
                    ]),
                ],
            ])
            ->add('paymentReference', TextType::class, [
                'label' => 'Référence du paiement',
                'required' => false,
                'attr' => [
                    'placeholder' => 'N° de transaction, chèque, etc.',
                    'class' => 'form-input',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Notes sur ce paiement',
                    'class' => 'form-textarea',
                    'rows' => 2,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'max_amount' => null,
        ]);
    }
}
