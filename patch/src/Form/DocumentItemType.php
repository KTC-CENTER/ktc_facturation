<?php

namespace App\Form;

use App\Entity\DocumentItem;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un produit',
                'required' => false,
                'attr' => ['class' => 'form-select product-select'],
                // Ajouter les data attributes pour le JavaScript
                'choice_attr' => function(?Product $product) {
                    if (!$product) {
                        return [];
                    }
                    return [
                        'data-name' => $product->getName(),
                        'data-code' => $product->getCode() ?? '',
                        'data-description' => $product->getDescription() ?? '',
                        'data-price' => $product->getUnitPriceFloat(),
                        'data-unit' => $product->getUnit() ?? '',
                    ];
                },
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('p')
                        ->where('p.isActive = true')
                        ->orderBy('p.name', 'ASC');
                },
            ])
            ->add('designation', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-input designation-input',
                    'placeholder' => 'Désignation',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-textarea description-input',
                    'rows' => 2,
                    'placeholder' => 'Description (optionnel)',
                ],
            ])
            ->add('quantity', NumberType::class, [
                'label' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-input qty-input',
                    'min' => '0.01',
                    'step' => '0.01',
                    'placeholder' => '1',
                ],
            ])
            ->add('unit', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'unité',
                ],
            ])
            // IMPORTANT: NumberType, pas MoneyType - évite l'affichage de FCFA
            ->add('unitPrice', NumberType::class, [
                'label' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-input price-input',
                    'min' => '0',
                    'step' => '0.01',
                    'placeholder' => '0',
                ],
            ])
            ->add('discount', NumberType::class, [
                'label' => false,
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-input discount-input',
                    'min' => '0',
                    'max' => '100',
                    'step' => '0.01',
                    'placeholder' => '0',
                ],
            ])
            ->add('sortOrder', HiddenType::class, [
                'attr' => ['class' => 'sort-order-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DocumentItem::class,
        ]);
    }
}
