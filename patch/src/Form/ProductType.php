<?php

namespace App\Form;

use App\Entity\Product;
use App\Repository\CompanySettingsRepository;
use App\Repository\ProductRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    private CompanySettingsRepository $settingsRepository;
    private ProductRepository $productRepository;

    public function __construct(CompanySettingsRepository $settingsRepository, ProductRepository $productRepository)
    {
        $this->settingsRepository = $settingsRepository;
        $this->productRepository = $productRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $settings = $this->settingsRepository->getSettings();
        $defaultMargin = $settings?->getDefaultMargin() ?? 30;
        $defaultTaxRate = $settings?->getDefaultTaxRate() ?? 19.25;

        // Récupérer les catégories existantes
        $existingCategories = $this->getExistingCategories();

        $builder
            // === Informations générales ===
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'placeholder' => 'Ex: Logiciel CHURCH 3.0',
                    'class' => 'form-input',
                ],
            ])
            ->add('code', TextType::class, [
                'label' => 'Code produit',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Auto-généré si vide',
                    'class' => 'form-input',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => array_flip(Product::TYPES),
                'attr' => [
                    'class' => 'form-select product-type-select',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'choices' => $existingCategories,
                'attr' => [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Sélectionner une catégorie',
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Unité',
                'required' => false,
                'choices' => [
                    'Unité' => 'unité',
                    'Pièce' => 'pièce',
                    'Licence' => 'licence',
                    'Forfait' => 'forfait',
                    'Heure' => 'heure',
                    'Jour' => 'jour',
                    'Mois' => 'mois',
                    'Année' => 'année',
                    'Lot' => 'lot',
                    'Kg' => 'kg',
                    'Mètre' => 'm',
                ],
                'placeholder' => 'Sélectionner une unité',
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

            // === Tarification ===
            ->add('purchasePrice', NumberType::class, [
                'label' => 'Prix d\'achat (HT)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-input purchase-price',
                    'step' => '0.01',
                    'min' => '0',
                ],
            ])
            ->add('margin', NumberType::class, [
                'label' => 'Marge (%)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => (string) $defaultMargin,
                    'class' => 'form-input margin-input',
                    'step' => '0.01',
                ],
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Prix de vente (HT)',
                'scale' => 2,
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-input unit-price',
                    'step' => '0.01',
                    'min' => '0',
                ],
            ])
            ->add('taxRate', NumberType::class, [
                'label' => 'Taux TVA (%)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => (string) $defaultTaxRate,
                    'class' => 'form-input',
                    'step' => '0.01',
                ],
            ])

            // === Statut ===
            ->add('isActive', CheckboxType::class, [
                'label' => 'Produit actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-checkbox',
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
                        'attr' => ['placeholder' => 'Ex: 3.0', 'class' => 'form-input'],
                    ])
                    ->add('licenseType', ChoiceType::class, [
                        'label' => 'Type de licence',
                        'required' => false,
                        'choices' => [
                            'Mono-poste' => 'Mono-poste',
                            'Multi-postes' => 'Multi-postes',
                            'Réseau' => 'Réseau',
                            'Cloud' => 'Cloud',
                            'Perpétuelle' => 'Perpétuelle',
                            'Annuelle' => 'Annuelle',
                            'Mensuelle' => 'Mensuelle',
                        ],
                        'placeholder' => 'Sélectionner',
                        'attr' => ['class' => 'form-select'],
                    ])
                    ->add('licenseDuration', IntegerType::class, [
                        'label' => 'Durée licence (mois)',
                        'required' => false,
                        'attr' => ['placeholder' => '0 = perpétuelle', 'class' => 'form-input', 'min' => 0],
                    ])
                    ->add('maxUsers', IntegerType::class, [
                        'label' => 'Utilisateurs max',
                        'required' => false,
                        'attr' => ['placeholder' => 'Nombre', 'class' => 'form-input', 'min' => 1],
                    ])
                ;
            }

            // Champs MATERIEL
            if ($type === Product::TYPE_MATERIEL) {
                $form
                    ->add('brand', TextType::class, [
                        'label' => 'Marque',
                        'required' => false,
                        'attr' => ['placeholder' => 'Ex: HP, Dell', 'class' => 'form-input'],
                    ])
                    ->add('model', TextType::class, [
                        'label' => 'Modèle',
                        'required' => false,
                        'attr' => ['placeholder' => 'Référence', 'class' => 'form-input'],
                    ])
                    ->add('warrantyMonths', IntegerType::class, [
                        'label' => 'Garantie (mois)',
                        'required' => false,
                        'attr' => ['placeholder' => '12', 'class' => 'form-input', 'min' => 0],
                    ])
                ;
            }

            // Champs SERVICE
            if ($type === Product::TYPE_SERVICE) {
                $form
                    ->add('durationHours', IntegerType::class, [
                        'label' => 'Durée (heures)',
                        'required' => false,
                        'attr' => ['placeholder' => '8', 'class' => 'form-input', 'min' => 0],
                    ])
                ;
            }

            // Caractéristiques (tous types)
            $form->add('characteristics', TextareaType::class, [
                'label' => 'Caractéristiques techniques',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: Core i5 / 8Go RAM / 256Go SSD',
                    'class' => 'form-textarea',
                    'rows' => 3,
                ],
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier, $defaultMargin, $defaultTaxRate) {
                $product = $event->getData();
                $formModifier($event->getForm(), $product?->getType());
                
                // Valeurs par défaut pour nouveau produit
                if ($product && $product->getId() === null) {
                    if ($product->getMargin() === null) {
                        $product->setMargin((string) $defaultMargin);
                    }
                    if ($product->getTaxRate() === null) {
                        $product->setTaxRate((string) $defaultTaxRate);
                    }
                    $product->setIsActive(true);
                }
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

    private function getExistingCategories(): array
    {
        $categories = [
            'Logiciels de gestion' => 'Logiciels de gestion',
            'Logiciels comptables' => 'Logiciels comptables',
            'Sécurité informatique' => 'Sécurité informatique',
            'Matériel informatique' => 'Matériel informatique',
            'Périphériques' => 'Périphériques',
            'Réseaux' => 'Réseaux',
            'Formation' => 'Formation',
            'Maintenance' => 'Maintenance',
            'Installation' => 'Installation',
            'Conseil' => 'Conseil',
            'Support technique' => 'Support technique',
        ];

        try {
            $existingCats = $this->productRepository->createQueryBuilder('p')
                ->select('DISTINCT p.category')
                ->where('p.category IS NOT NULL')
                ->andWhere('p.category != :empty')
                ->setParameter('empty', '')
                ->getQuery()
                ->getResult();

            foreach ($existingCats as $cat) {
                if ($cat['category'] && !isset($categories[$cat['category']])) {
                    $categories[$cat['category']] = $cat['category'];
                }
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }

        asort($categories);
        return $categories;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
