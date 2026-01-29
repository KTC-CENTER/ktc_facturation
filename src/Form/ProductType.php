<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'placeholder' => 'Nom du produit ou service',
                    'class' => 'form-input',
                ],
            ])
            ->add('code', TextType::class, [
                'label' => 'Code produit',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Code auto-généré si vide',
                    'class' => 'form-input',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => array_flip(Product::TYPES),
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description détaillée du produit',
                    'class' => 'form-textarea',
                    'rows' => 3,
                ],
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Prix unitaire',
                'currency' => 'XAF',
                'divisor' => 1,
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-input',
                ],
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unité',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex: licence, pièce, heure, jour',
                    'class' => 'form-input',
                ],
            ])
        ;

        // Champs conditionnels selon le type
        $formModifier = function (FormInterface $form, ?string $type = null) {
            // Champs LOGICIEL
            if ($type === Product::TYPE_LOGICIEL) {
                $form
                    ->add('version', TextType::class, [
                        'label' => 'Version',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'ex: 2024.1',
                            'class' => 'form-input',
                        ],
                    ])
                    ->add('licenseType', TextType::class, [
                        'label' => 'Type de licence',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'ex: Perpétuelle, Annuelle, Mensuelle',
                            'class' => 'form-input',
                        ],
                    ])
                    ->add('licenseDuration', IntegerType::class, [
                        'label' => 'Durée licence (mois)',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Nombre de mois (0 = perpétuelle)',
                            'class' => 'form-input',
                            'min' => 0,
                        ],
                    ])
                    ->add('maxUsers', IntegerType::class, [
                        'label' => 'Utilisateurs max',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Nombre d\'utilisateurs',
                            'class' => 'form-input',
                            'min' => 1,
                        ],
                    ])
                ;
            }

            // Champs MATERIEL
            if ($type === Product::TYPE_MATERIEL) {
                $form
                    ->add('brand', TextType::class, [
                        'label' => 'Marque',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'ex: HP, Dell, Lenovo',
                            'class' => 'form-input',
                        ],
                    ])
                    ->add('model', TextType::class, [
                        'label' => 'Modèle',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Référence du modèle',
                            'class' => 'form-input',
                        ],
                    ])
                    ->add('warrantyMonths', IntegerType::class, [
                        'label' => 'Garantie (mois)',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Durée de garantie',
                            'class' => 'form-input',
                            'min' => 0,
                        ],
                    ])
                ;
            }

            // Champs SERVICE
            if ($type === Product::TYPE_SERVICE) {
                $form
                    ->add('durationHours', IntegerType::class, [
                        'label' => 'Durée (heures)',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Durée de la prestation',
                            'class' => 'form-input',
                            'min' => 0,
                        ],
                    ])
                ;
            }

            // Caractéristiques supplémentaires (tous types)
            $form->add('characteristics', TextareaType::class, [
                'label' => 'Caractéristiques',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Caractéristiques techniques (une par ligne)',
                    'class' => 'form-textarea',
                    'rows' => 4,
                ],
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $product = $event->getData();
                $formModifier($event->getForm(), $product?->getType());
            }
        );

        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $type = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $type);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
