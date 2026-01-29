<?php

namespace App\Form;

use App\Entity\DocumentShare;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ShareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shareType', ChoiceType::class, [
                'label' => 'Mode de partage',
                'choices' => [
                    'Générer un lien' => DocumentShare::TYPE_LINK,
                    'Envoyer par email' => DocumentShare::TYPE_EMAIL,
                    'Partager via WhatsApp' => DocumentShare::TYPE_WHATSAPP,
                ],
                'expanded' => true,
                'attr' => [
                    'class' => 'space-y-2',
                ],
            ])
            ->add('recipientEmail', EmailType::class, [
                'label' => 'Email du destinataire',
                'required' => false,
                'attr' => [
                    'placeholder' => 'email@exemple.com',
                    'class' => 'form-input',
                ],
            ])
            ->add('recipientPhone', TelType::class, [
                'label' => 'Téléphone WhatsApp',
                'required' => false,
                'attr' => [
                    'placeholder' => '+237 6XX XXX XXX',
                    'class' => 'form-input',
                ],
                'help' => 'Numéro avec indicatif pays (ex: +237)',
            ])
            ->add('validityHours', IntegerType::class, [
                'label' => 'Durée de validité',
                'mapped' => false,
                'data' => 168, // 7 jours par défaut
                'attr' => [
                    'placeholder' => '168',
                    'class' => 'form-input',
                    'min' => 1,
                    'max' => 8760, // 1 an max
                ],
                'help' => 'En heures (168 = 7 jours)',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message personnalisé',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Message à inclure dans l\'email ou WhatsApp',
                    'class' => 'form-textarea',
                    'rows' => 3,
                ],
            ])
        ;

        // Validation dynamique selon le type de partage
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data instanceof DocumentShare) {
                $shareType = $data->getShareType();

                if ($shareType === DocumentShare::TYPE_EMAIL && empty($data->getRecipientEmail())) {
                    $form->get('recipientEmail')->addError(
                        new \Symfony\Component\Form\FormError('L\'email est requis pour ce mode de partage')
                    );
                }

                if ($shareType === DocumentShare::TYPE_WHATSAPP && empty($data->getRecipientPhone())) {
                    $form->get('recipientPhone')->addError(
                        new \Symfony\Component\Form\FormError('Le numéro WhatsApp est requis pour ce mode de partage')
                    );
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DocumentShare::class,
        ]);
    }
}
