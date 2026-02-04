<?php

namespace App\Service;

use App\Entity\Proforma;
use App\Entity\Invoice;
use App\Entity\EmailTemplate;
use App\Entity\DocumentShare;
use App\Entity\User;
use App\Repository\EmailTemplateRepository;
use App\Repository\CompanySettingsRepository;
use Psr\Log\LoggerInterface;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\SendSmtpEmail;
use SendinBlue\Client\Model\SendSmtpEmailTo;
use SendinBlue\Client\Model\SendSmtpEmailAttachment;
use SendinBlue\Client\Model\SendSmtpEmailSender;
use GuzzleHttp\Client;

class BrevoMailerService
{
    private ?TransactionalEmailsApi $apiInstance = null;
    private EmailTemplateRepository $templateRepository;
    private CompanySettingsRepository $settingsRepository;
    private PdfGeneratorService $pdfGenerator;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        EmailTemplateRepository $templateRepository,
        CompanySettingsRepository $settingsRepository,
        PdfGeneratorService $pdfGenerator,
        LoggerInterface $logger,
        string $brevoApiKey,
        string $mailSenderEmail,
        string $mailSenderName
    ) {
        $this->templateRepository = $templateRepository;
        $this->settingsRepository = $settingsRepository;
        $this->pdfGenerator = $pdfGenerator;
        $this->logger = $logger;
        $this->apiKey = $brevoApiKey;
        $this->senderEmail = $mailSenderEmail;
        $this->senderName = $mailSenderName;
    }

    private function getApiInstance(): TransactionalEmailsApi
    {
        if ($this->apiInstance === null) {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
            $this->apiInstance = new TransactionalEmailsApi(new Client(), $config);
        }

        return $this->apiInstance;
    }

    public function sendProforma(Proforma $proforma, string $recipientEmail, ?string $message = null): bool
    {
        $template = $this->templateRepository->findDefaultByType('proforma');
        $settings = $this->settingsRepository->getOrCreateSettings();

        $subject = $this->processTemplate(
            $template?->getSubject() ?? 'Proforma {reference}',
            $proforma,
            $settings
        );

        $body = $this->processTemplate(
            $template?->getBodyHtml() ?? $this->getDefaultProformaBody(),
            $proforma,
            $settings
        );

        if ($message) {
            $body .= "\n\nMessage personnel:\n" . $message;
        }

        $pdfPath = $this->pdfGenerator->generateProformaPdf($proforma);
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';

        $htmlBody = $this->wrapInHtmlTemplate($body, "Proforma {$proforma->getReference()}", $companyName);

        return $this->send($recipientEmail, $subject, $htmlBody, $pdfPath, "proforma_{$proforma->getReference()}.pdf");
    }

    public function sendInvoice(Invoice $invoice, string $recipientEmail, ?string $message = null): bool
    {
        $template = $this->templateRepository->findDefaultByType('invoice');
        $settings = $this->settingsRepository->getOrCreateSettings();

        $subject = $this->processTemplate(
            $template?->getSubject() ?? 'Facture {reference}',
            $invoice,
            $settings
        );

        $body = $this->processTemplate(
            $template?->getBodyHtml() ?? $this->getDefaultInvoiceBody(),
            $invoice,
            $settings
        );

        if ($message) {
            $body .= "\n\nMessage personnel:\n" . $message;
        }

        $pdfPath = $this->pdfGenerator->generateInvoicePdf($invoice);
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';

        $htmlBody = $this->wrapInHtmlTemplate($body, "Facture {$invoice->getReference()}", $companyName);

        return $this->send($recipientEmail, $subject, $htmlBody, $pdfPath, "facture_{$invoice->getReference()}.pdf");
    }

    public function sendShareLink(DocumentShare $share): bool
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $document = $share->getProforma() ?? $share->getInvoice();
        $docType = $share->getProforma() ? 'proforma' : 'facture';
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';

        $subject = "Lien de consultation - {$docType} {$document->getReference()}";
        $htmlBody = $this->buildShareEmailHtml($share, $docType, $companyName);

        return $this->send($share->getRecipientEmail(), $subject, $htmlBody);
    }

    public function sendDocumentShare(DocumentShare $share, ?string $personalMessage = null): bool
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $document = $share->getProforma() ?? $share->getInvoice();
        $docType = $share->getProforma() ? 'proforma' : 'facture';
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';

        $subject = ucfirst($docType) . " {$document->getReference()}";
        $htmlBody = $this->buildShareEmailHtml($share, $docType, $companyName, $personalMessage);

        $pdfPath = null;
        $pdfName = null;
        if ($share->getProforma()) {
            $pdfPath = $this->pdfGenerator->generateProformaPdf($share->getProforma());
            $pdfName = "proforma_{$share->getProforma()->getReference()}.pdf";
        } elseif ($share->getInvoice()) {
            $pdfPath = $this->pdfGenerator->generateInvoicePdf($share->getInvoice());
            $pdfName = "facture_{$share->getInvoice()->getReference()}.pdf";
        }

        return $this->send($share->getRecipientEmail(), $subject, $htmlBody, $pdfPath, $pdfName);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     * @param string $resetUrl URL ABSOLUE de réinitialisation
     */
    public function sendPasswordResetEmail(User $user, string $resetUrl): bool
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';

        $subject = "Réinitialisation de votre mot de passe - {$companyName}";
        $htmlBody = $this->buildPasswordResetHtml($user, $resetUrl, $companyName);

        return $this->send($user->getEmail(), $subject, $htmlBody);
    }

    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $attachmentPath = null,
        ?string $attachmentName = null
    ): bool {
        try {
            $email = new SendSmtpEmail();
            $email->setSubject($subject);
            $email->setHtmlContent($htmlBody);
            $email->setTextContent(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $htmlBody)));
            
            $sender = new SendSmtpEmailSender();
            $sender->setEmail($this->senderEmail);
            $sender->setName($this->senderName);
            $email->setSender($sender);

            $recipient = new SendSmtpEmailTo();
            $recipient->setEmail($to);
            $email->setTo([$recipient]);

            if ($attachmentPath && file_exists($attachmentPath)) {
                $attachment = new SendSmtpEmailAttachment();
                $attachment->setContent(base64_encode(file_get_contents($attachmentPath)));
                $attachment->setName($attachmentName ?? basename($attachmentPath));
                $email->setAttachment([$attachment]);
            }

            $this->getApiInstance()->sendTransacEmail($email);
            
            $this->logger->info('Email envoyé avec succès', ['to' => $to, 'subject' => $subject]);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function processTemplate(string $template, $document, $settings): string
    {
        $client = $document->getClient();
        
        $replacements = [
            '{reference}' => $document->getReference(),
            '{client_name}' => $client->getName(),
            '{client_email}' => $client->getEmail() ?? '',
            '{total_ht}' => number_format($document->getTotalHTFloat(), 0, ',', ' '),
            '{total_ttc}' => number_format($document->getTotalTTCFloat(), 0, ',', ' '),
            '{currency}' => $settings->getCurrency() ?? 'FCFA',
            '{company_name}' => $settings->getCompanyName() ?? 'KTC-Center',
            '{issue_date}' => $document->getIssueDate()->format('d/m/Y'),
        ];

        if ($document instanceof Proforma) {
            $replacements['{valid_until}'] = $document->getValidUntil()->format('d/m/Y');
        }

        if ($document instanceof Invoice) {
            $replacements['{due_date}'] = $document->getDueDate()?->format('d/m/Y') ?? '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function getDefaultProformaBody(): string
    {
        return "Bonjour {client_name},\n\nVeuillez trouver ci-joint notre proforma n° {reference} d'un montant de {total_ttc} {currency}.\n\nCette proforma est valable jusqu'au {valid_until}.\n\nN'hésitez pas à nous contacter pour toute question.\n\nCordialement,\n{company_name}";
    }

    private function getDefaultInvoiceBody(): string
    {
        return "Bonjour {client_name},\n\nVeuillez trouver ci-joint notre facture n° {reference} d'un montant de {total_ttc} {currency}.\n\nDate d'échéance : {due_date}\n\nNous vous remercions pour votre confiance.\n\nCordialement,\n{company_name}";
    }

    // =========================================================================
    // HTML EMAIL TEMPLATES
    // =========================================================================

    private function wrapInHtmlTemplate(string $textContent, string $title, string $companyName): string
    {
        $htmlContent = nl2br(htmlspecialchars($textContent));
        $year = date('Y');
        
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars($title) . '</title></head>'
            . '<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;"><tr><td style="padding:30px 20px;">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin:0 auto;max-width:600px;">'
            // Header
            . '<tr><td style="background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);padding:30px 40px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;letter-spacing:0.5px;">' . htmlspecialchars($companyName) . '</h1>'
            . '<p style="color:#A3C4DC;margin:8px 0 0;font-size:13px;">' . htmlspecialchars($title) . '</p>'
            . '</td></tr>'
            // Body
            . '<tr><td style="background-color:#ffffff;padding:35px 40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">'
            . '<div style="color:#374151;font-size:15px;line-height:1.7;">' . $htmlContent . '</div>'
            . '</td></tr>'
            // Footer
            . '<tr><td style="background-color:#f9fafb;padding:25px 40px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;text-align:center;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0;">Cet email a été envoyé automatiquement par <strong>' . htmlspecialchars($companyName) . '</strong></p>'
            . '<p style="color:#9ca3af;font-size:11px;margin:8px 0 0;">&copy; ' . $year . ' ' . htmlspecialchars($companyName) . ' &mdash; Tous droits réservés</p>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private function buildPasswordResetHtml(User $user, string $resetUrl, string $companyName): string
    {
        $userName = htmlspecialchars($user->getFullName());
        $safeUrl = htmlspecialchars($resetUrl);
        $year = date('Y');

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Réinitialisation de mot de passe</title></head>'
            . '<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;"><tr><td style="padding:30px 20px;">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin:0 auto;max-width:600px;">'
            // Header
            . '<tr><td style="background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);padding:30px 40px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<div style="width:60px;height:60px;margin:0 auto 15px;background:rgba(255,255,255,0.15);border-radius:50%;line-height:60px;font-size:28px;">&#x1F510;</div>'
            . '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">Réinitialisation de mot de passe</h1>'
            . '<p style="color:#A3C4DC;margin:8px 0 0;font-size:13px;">' . htmlspecialchars($companyName) . '</p>'
            . '</td></tr>'
            // Body
            . '<tr><td style="background-color:#ffffff;padding:35px 40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">'
            . '<p style="color:#374151;font-size:16px;line-height:1.6;margin:0 0 20px;">Bonjour <strong>' . $userName . '</strong>,</p>'
            . '<p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 25px;">Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour en définir un nouveau :</p>'
            // CTA Button
            . '<div style="text-align:center;margin:30px 0;">'
            . '<a href="' . $safeUrl . '" style="display:inline-block;background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:600;letter-spacing:0.3px;" target="_blank">Réinitialiser mon mot de passe</a>'
            . '</div>'
            // Warning
            . '<div style="background-color:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:16px 20px;margin:25px 0 15px;">'
            . '<p style="color:#92400e;font-size:13px;margin:0;line-height:1.5;">&#x23F0; <strong>Ce lien expire dans 1 heure.</strong><br>Si vous n\'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>'
            . '</div>'
            // Fallback URL
            . '<div style="background-color:#f3f4f6;border-radius:8px;padding:14px 18px;margin-top:20px;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0 0 6px;">Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :</p>'
            . '<p style="color:#2563eb;font-size:12px;margin:0;word-break:break-all;"><a href="' . $safeUrl . '" style="color:#2563eb;text-decoration:underline;">' . $safeUrl . '</a></p>'
            . '</div>'
            . '</td></tr>'
            // Footer
            . '<tr><td style="background-color:#f9fafb;padding:25px 40px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;text-align:center;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0;">Cet email a été envoyé automatiquement par <strong>' . htmlspecialchars($companyName) . '</strong></p>'
            . '<p style="color:#9ca3af;font-size:11px;margin:8px 0 0;">&copy; ' . $year . ' ' . htmlspecialchars($companyName) . ' &mdash; Tous droits réservés</p>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private function buildShareEmailHtml(DocumentShare $share, string $docType, string $companyName, ?string $personalMessage = null): string
    {
        $url = htmlspecialchars($share->getShareUrl());
        $expiresAt = $share->getExpiresAt()->format('d/m/Y à H:i');
        $document = $share->getProforma() ?? $share->getInvoice();
        $reference = htmlspecialchars($document->getReference());
        $docTypeLabel = ucfirst($docType);
        $year = date('Y');

        $personalHtml = '';
        if ($personalMessage) {
            $safeMsg = nl2br(htmlspecialchars($personalMessage));
            $personalHtml = '<div style="background-color:#eff6ff;border-left:4px solid #2E86AB;border-radius:0 8px 8px 0;padding:16px 20px;margin:0 0 25px;">'
                . '<p style="color:#1e40af;font-size:13px;font-weight:600;margin:0 0 6px;">Message :</p>'
                . '<p style="color:#374151;font-size:14px;margin:0;line-height:1.6;">' . $safeMsg . '</p>'
                . '</div>';
        }

        $emoji = $docType === 'proforma' ? '&#x1F4CB;' : '&#x1F9FE;';

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . $docTypeLabel . ' ' . $reference . '</title></head>'
            . '<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;"><tr><td style="padding:30px 20px;">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin:0 auto;max-width:600px;">'
            // Header
            . '<tr><td style="background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);padding:30px 40px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<div style="width:60px;height:60px;margin:0 auto 15px;background:rgba(255,255,255,0.15);border-radius:50%;line-height:60px;font-size:28px;">' . $emoji . '</div>'
            . '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">' . $docTypeLabel . ' ' . $reference . '</h1>'
            . '<p style="color:#A3C4DC;margin:8px 0 0;font-size:13px;">' . htmlspecialchars($companyName) . '</p>'
            . '</td></tr>'
            // Body
            . '<tr><td style="background-color:#ffffff;padding:35px 40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">'
            . '<p style="color:#374151;font-size:16px;line-height:1.6;margin:0 0 20px;">Bonjour,</p>'
            . '<p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 25px;">Un document vous a été partagé. Cliquez sur le bouton ci-dessous pour le consulter :</p>'
            . $personalHtml
            // Document info card
            . '<div style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:18px 22px;margin:0 0 25px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Type :</td><td style="color:#111827;font-size:13px;font-weight:600;text-align:right;padding:4px 0;">' . $docTypeLabel . '</td></tr>'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Référence :</td><td style="color:#111827;font-size:13px;font-weight:600;text-align:right;padding:4px 0;">' . $reference . '</td></tr>'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Expire le :</td><td style="color:#dc2626;font-size:13px;font-weight:600;text-align:right;padding:4px 0;">' . $expiresAt . '</td></tr>'
            . '</table></div>'
            // CTA Button
            . '<div style="text-align:center;margin:30px 0;">'
            . '<a href="' . $url . '" style="display:inline-block;background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:600;letter-spacing:0.3px;" target="_blank">&#x1F4C4; Consulter le document</a>'
            . '</div>'
            // Fallback URL
            . '<div style="background-color:#f3f4f6;border-radius:8px;padding:14px 18px;margin-top:20px;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0 0 6px;">Si le bouton ne fonctionne pas, copiez-collez ce lien :</p>'
            . '<p style="color:#2563eb;font-size:12px;margin:0;word-break:break-all;"><a href="' . $url . '" style="color:#2563eb;text-decoration:underline;">' . $url . '</a></p>'
            . '</div>'
            . '</td></tr>'
            // Footer
            . '<tr><td style="background-color:#f9fafb;padding:25px 40px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;text-align:center;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0;">Cet email a été envoyé automatiquement par <strong>' . htmlspecialchars($companyName) . '</strong></p>'
            . '<p style="color:#9ca3af;font-size:11px;margin:8px 0 0;">&copy; ' . $year . ' ' . htmlspecialchars($companyName) . ' &mdash; Tous droits réservés</p>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
