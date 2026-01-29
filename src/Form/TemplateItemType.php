<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\TemplateItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function (Product $product) {
                    return sprintf('%s - %s', $product->getCode(), $product->getName());
                },
                'label' => 'Produit',
                'placeholder' => 'Sélectionner ou saisir manuellement',
                'required' => false,
                'attr' => [
                    'class' => 'form-select product-select',
                ],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('p')
                        ->where('p.isActive = true')
                        ->orderBy('p.name', 'ASC');
                },
            ])
            ->add('designation', TextType::class, [
                'label' => 'Désignation',
                'attr' => [
                    'placeholder' => 'Désignation de l\'article',
                    'class' => 'form-input',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description détaillée',
                    'class' => 'form-textarea',
                    'rows' => 2,
                ],
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'scale' => 2,
                'attr' => [
                    'placeholder' => '1',
                    'class' => 'form-input',
                    'min' => 0,
                    'step' => '0.01',
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
            ->add('discount', NumberType::class, [
                'label' => 'Remise (%)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-input',
                    'min' => 0,
                    'max' => 100,
                    'step' => '0.01',
                ],
            ])
            ->add('sortOrder', HiddenType::class, [
                'attr' => [
                    'class' => 'sort-order-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TemplateItem::class,
        ]);
    }
}
