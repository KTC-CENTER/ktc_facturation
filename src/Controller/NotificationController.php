<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\CompanySettingsRepository;
use App\Service\BrevoMailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_COMMERCIAL')]
class NotificationController extends AbstractController
{
    public function __construct(
        private ClientRepository $clientRepository,
        private CompanySettingsRepository $settingsRepository,
        private BrevoMailerService $mailer
    ) {}

    #[Route('', name: 'app_notification_index', methods: ['GET'])]
    public function index(): Response
    {
        $clients = $this->clientRepository->findBy(
            ['isArchived' => false],
            ['name' => 'ASC']
        );

        // Only clients with email
        $clients = array_filter($clients, fn($c) => !empty($c->getEmail()));

        return $this->render('notification/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/send', name: 'app_notification_send', methods: ['POST'])]
    public function send(Request $request): Response
    {
        $clientIds = $request->request->all('clients');
        $subject = $request->request->get('subject', '');
        $message = $request->request->get('message', '');

        if (empty($clientIds) || empty($subject) || empty($message)) {
            $this->addFlash('error', 'Veuillez sélectionner des clients et remplir le sujet et le message.');
            return $this->redirectToRoute('app_notification_index');
        }

        $settings = $this->settingsRepository->getOrCreateSettings();
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';

        $sent = 0;
        $failed = 0;

        foreach ($clientIds as $clientId) {
            $client = $this->clientRepository->find($clientId);
            if (!$client || !$client->getEmail()) continue;

            $htmlBody = $this->buildMassEmailHtml($companyName, $client->getName(), $subject, $message);
            
            if ($this->mailer->send($client->getEmail(), $subject, $htmlBody)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($sent > 0) {
            $this->addFlash('success', "{$sent} email(s) envoyé(s) avec succès.");
        }
        if ($failed > 0) {
            $this->addFlash('error', "{$failed} email(s) en échec.");
        }

        return $this->redirectToRoute('app_notification_index');
    }

    private function buildMassEmailHtml(string $companyName, string $clientName, string $subject, string $message): string
    {
        $safeCompany = htmlspecialchars($companyName);
        $safeClient = htmlspecialchars($clientName);
        $safeSubject = htmlspecialchars($subject);
        $htmlMessage = nl2br(htmlspecialchars($message));
        $year = date('Y');

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:\'Segoe UI\',Roboto,Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;"><tr><td style="padding:30px 20px;">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin:0 auto;max-width:600px;">'
            . '<tr><td style="background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);padding:30px 40px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">' . $safeCompany . '</h1>'
            . '<p style="color:#A3C4DC;margin:8px 0 0;font-size:13px;">' . $safeSubject . '</p></td></tr>'
            . '<tr><td style="background-color:#ffffff;padding:35px 40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">'
            . '<p style="color:#374151;font-size:16px;line-height:1.6;margin:0 0 20px;">Bonjour <strong>' . $safeClient . '</strong>,</p>'
            . '<div style="color:#374151;font-size:15px;line-height:1.7;">' . $htmlMessage . '</div>'
            . '</td></tr>'
            . '<tr><td style="background-color:#f9fafb;padding:25px 40px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;text-align:center;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0;">' . $safeCompany . '</p>'
            . '<p style="color:#9ca3af;font-size:11px;margin:8px 0 0;">&copy; ' . $year . ' ' . $safeCompany . '</p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }
}
