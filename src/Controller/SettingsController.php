<?php

namespace App\Controller;

use App\Entity\CompanySettings;
use App\Entity\EmailTemplate;
use App\Form\CompanySettingsType;
use App\Form\EmailTemplateType;
use App\Repository\CompanySettingsRepository;
use App\Repository\EmailTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/settings')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class SettingsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanySettingsRepository $settingsRepository,
        private EmailTemplateRepository $emailTemplateRepository,
        private string $uploadsDirectory
    ) {}

    #[Route('', name: 'app_settings_index', methods: ['GET'])]
    public function index(): Response
    {
        $settings = $this->getOrCreateSettings();
        $emailTemplates = $this->emailTemplateRepository->findAll();

        return $this->render('settings/index.html.twig', [
            'settings' => $settings,
            'emailTemplates' => $emailTemplates,
        ]);
    }

    #[Route('/company', name: 'app_settings_company', methods: ['GET', 'POST'])]
    public function company(Request $request, SluggerInterface $slugger): Response
    {
        $settings = $this->getOrCreateSettings();
        $form = $this->createForm(CompanySettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du logo
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->uploadsDirectory . '/logo',
                        $newFilename
                    );
                    
                    // Supprimer l'ancien logo si existe
                    if ($settings->getLogoPath()) {
                        $oldLogoPath = $this->uploadsDirectory . '/logo/' . $settings->getLogoPath();
                        if (file_exists($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                    }
                    
                    $settings->setLogoPath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du logo.');
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Paramètres de l\'entreprise mis à jour.');
            return $this->redirectToRoute('app_settings_index');
        }

        return $this->render('settings/company.html.twig', [
            'settings' => $settings,
            'form' => $form,
        ]);
    }

    #[Route('/invoicing', name: 'app_settings_invoicing', methods: ['GET', 'POST'])]
    public function invoicing(Request $request): Response
    {
        $settings = $this->getOrCreateSettings();
        $form = $this->createForm(CompanySettingsType::class, $settings, [
            'section' => 'invoicing',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Paramètres de facturation mis à jour.');
            return $this->redirectToRoute('app_settings_index');
        }

        return $this->render('settings/invoicing.html.twig', [
            'settings' => $settings,
            'form' => $form,
        ]);
    }

    #[Route('/email', name: 'app_settings_email', methods: ['GET', 'POST'])]
    public function email(Request $request): Response
    {
        $settings = $this->getOrCreateSettings();
        $form = $this->createForm(CompanySettingsType::class, $settings, [
            'section' => 'email',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Paramètres email mis à jour.');
            return $this->redirectToRoute('app_settings_index');
        }

        return $this->render('settings/email.html.twig', [
            'settings' => $settings,
            'form' => $form,
        ]);
    }

    #[Route('/email-templates', name: 'app_settings_email_templates', methods: ['GET'])]
    public function emailTemplates(): Response
    {
        $templates = $this->emailTemplateRepository->findAll();

        return $this->render('settings/email_templates.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route('/email-templates/new', name: 'app_settings_email_template_new', methods: ['GET', 'POST'])]
    public function newEmailTemplate(Request $request): Response
    {
        $template = new EmailTemplate();
        $form = $this->createForm(EmailTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $this->addFlash('success', 'Modèle d\'email créé.');
            return $this->redirectToRoute('app_settings_email_templates');
        }

        return $this->render('settings/email_template_form.html.twig', [
            'template' => $template,
            'form' => $form,
        ]);
    }

    #[Route('/email-templates/{id}/edit', name: 'app_settings_email_template_edit', methods: ['GET', 'POST'])]
    public function editEmailTemplate(Request $request, EmailTemplate $template): Response
    {
        $form = $this->createForm(EmailTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Modèle d\'email modifié.');
            return $this->redirectToRoute('app_settings_email_templates');
        }

        return $this->render('settings/email_template_form.html.twig', [
            'template' => $template,
            'form' => $form,
        ]);
    }

    #[Route('/email-templates/{id}/delete', name: 'app_settings_email_template_delete', methods: ['POST'])]
    public function deleteEmailTemplate(Request $request, EmailTemplate $template): Response
    {
        if ($this->isCsrfTokenValid('delete' . $template->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($template);
            $this->entityManager->flush();

            $this->addFlash('success', 'Modèle d\'email supprimé.');
        }

        return $this->redirectToRoute('app_settings_email_templates');
    }

    private function getOrCreateSettings(): CompanySettings
    {
        $settings = $this->settingsRepository->findOneBy([]);
        
        if (!$settings) {
            $settings = new CompanySettings();
            $settings->setCompanyName('KTC-Center Sarl');
            $settings->setAddress('Yaoundé, Cameroun');
            $settings->setDefaultTaxRate('19.25');
            $settings->setCurrency('FCFA');
            $settings->setDefaultValidityDays(30);
            $settings->setDefaultPaymentDays(30);
            $settings->setProformaPrefix('PROV');
            $settings->setInvoicePrefix('FAC');
            
            $this->entityManager->persist($settings);
            $this->entityManager->flush();
        }

        return $settings;
    }
}
