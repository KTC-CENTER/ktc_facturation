<?php

namespace App\Form;

use App\Entity\CompanySettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class CompanySettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $section = $options['section'] ?? 'company';

        if ($section === 'company') {
            $this->buildCompanyFields($builder);
        } elseif ($section === 'invoicing') {
            $this->buildInvoicingFields($builder);
        } elseif ($section === 'email') {
            $this->buildEmailFields($builder);
        }
    }

    private function buildCompanyFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'attr' => [
                    'placeholder' => 'Nom de votre entreprise',
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
                'attr' => [
                    'placeholder' => 'Pays',
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
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'email@entreprise.com',
                    'class' => 'form-input',
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://www.entreprise.com',
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
            ->add('taxId', TextType::class, [
                'label' => 'N° Contribuable',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Numéro d\'identification fiscale',
                    'class' => 'form-input',
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'form-input',
                ],
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WebP)',
                    ]),
                ],
            ])
        ;
    }

    private function buildInvoicingFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('defaultTaxRate', NumberType::class, [
                'label' => 'Taux TVA par défaut (%)',
                'scale' => 2,
                'attr' => [
                    'placeholder' => '19.25',
                    'class' => 'form-input',
                    'step' => '0.01',
                ],
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise par défaut',
                'choices' => [
                    'Franc CFA (FCFA)' => 'FCFA',
                    'Euro (EUR)' => 'EUR',
                    'Dollar US (USD)' => 'USD',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('defaultValidityDays', IntegerType::class, [
                'label' => 'Validité proforma (jours)',
                'attr' => [
                    'placeholder' => '30',
                    'class' => 'form-input',
                    'min' => 1,
                ],
            ])
            ->add('defaultPaymentDays', IntegerType::class, [
                'label' => 'Échéance facture (jours)',
                'attr' => [
                    'placeholder' => '30',
                    'class' => 'form-input',
                    'min' => 1,
                ],
            ])
            ->add('proformaPrefix', TextType::class, [
                'label' => 'Préfixe proforma',
                'attr' => [
                    'placeholder' => 'PROV',
                    'class' => 'form-input',
                ],
            ])
            ->add('invoicePrefix', TextType::class, [
                'label' => 'Préfixe facture',
                'attr' => [
                    'placeholder' => 'FAC',
                    'class' => 'form-input',
                ],
            ])
            ->add('defaultProformaConditions', TextareaType::class, [
                'label' => 'Conditions par défaut',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Conditions générales de vente',
                    'class' => 'form-textarea',
                    'rows' => 5,
                ],
            ])
            ->add('defaultPaymentTerms', TextareaType::class, [
                'label' => 'Notes par défaut',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Notes à inclure sur les documents',
                    'class' => 'form-textarea',
                    'rows' => 3,
                ],
            ])
        ;
    }

    private function buildEmailFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('brevoApiKey', TextType::class, [
                'label' => 'Clé API Brevo',
                'required' => false,
                'attr' => [
                    'placeholder' => 'xkeysib-xxxx...',
                    'class' => 'form-input',
                ],
                'help' => 'Obtenez votre clé API sur app.brevo.com',
            ])
            ->add('senderEmail', EmailType::class, [
                'label' => 'Email expéditeur',
                'required' => false,
                'attr' => [
                    'placeholder' => 'facturation@entreprise.com',
                    'class' => 'form-input',
                ],
            ])
            ->add('senderName', TextType::class, [
                'label' => 'Nom expéditeur',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Facturation - Entreprise',
                    'class' => 'form-input',
                ],
            ])
            ->add('replyToEmail', EmailType::class, [
                'label' => 'Email de réponse',
                'required' => false,
                'attr' => [
                    'placeholder' => 'contact@entreprise.com',
                    'class' => 'form-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompanySettings::class,
            'section' => 'company',
        ]);
    }
}
