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
use GuzzleHttp\Client;

class BrevoMailerService
{
    private ?Client $httpClient = null;
    private EmailTemplateRepository $templateRepository;
    private CompanySettingsRepository $settingsRepository;
    private PdfGeneratorService $pdfGenerator;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $senderEmail;
    private string $senderName;

    // ===== BREVO (ex-Sendinblue) — nouveau endpoint =====
    private const BREVO_API_URL = 'https://api.brevo.com';

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

    private function getHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client([
                'base_uri' => self::BREVO_API_URL,
                'headers' => [
                    'accept'       => 'application/json',
                    'content-type' => 'application/json',
                    'api-key'      => $this->apiKey,
                ],
                'timeout' => 30,
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Envoi générique d'un email (utilisé par NotificationController, etc.)
     */
    public function sendRawEmail(string $toEmail, string $subject, string $htmlContent, ?string $pdfPath = null, ?string $pdfName = null): bool
    {
        return $this->send($toEmail, $subject, $htmlContent, $pdfPath, $pdfName);
    }

    /**
     * Envoie un email via l'API Brevo v3 (POST /v3/smtp/email)
     * $pdfPath : chemin fichier OU null
     */
    private function send(string $toEmail, string $subject, string $htmlContent, ?string $pdfPath = null, ?string $pdfName = null): bool
    {
        $payload = [
            'sender'      => ['name' => $this->senderName, 'email' => $this->senderEmail],
            'to'          => [['email' => $toEmail]],
            'subject'     => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlContent)),
        ];

        if ($pdfPath && $pdfName && file_exists($pdfPath)) {
            $payload['attachment'] = [[
                'content' => base64_encode(file_get_contents($pdfPath)),
                'name'    => $pdfName,
            ]];
        }

        return $this->doSend($payload, $toEmail);
    }

    /**
     * Envoie un email avec contenu PDF brut (bytes) au lieu d'un chemin fichier
     */
    private function sendWithPdfContent(string $toEmail, string $subject, string $htmlContent, ?string $pdfContent = null, ?string $pdfName = null): bool
    {
        $payload = [
            'sender'      => ['name' => $this->senderName, 'email' => $this->senderEmail],
            'to'          => [['email' => $toEmail]],
            'subject'     => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlContent)),
        ];

        if ($pdfContent && $pdfName) {
            $payload['attachment'] = [[
                'content' => base64_encode($pdfContent),
                'name'    => $pdfName,
            ]];
        }

        return $this->doSend($payload, $toEmail);
    }

    /**
     * Appel HTTP POST réel vers Brevo
     */
    private function doSend(array $payload, string $toEmail): bool
    {
        try {
            $response = $this->getHttpClient()->post('/v3/smtp/email', [
                'json' => $payload,
            ]);

            $code = $response->getStatusCode();
            if ($code >= 200 && $code < 300) {
                $this->logger->info('Email envoyé via Brevo', ['to' => $toEmail]);
                return true;
            }

            $this->logger->error('Brevo réponse inattendue', [
                'to'     => $toEmail,
                'status' => $code,
                'body'   => (string) $response->getBody(),
            ]);
            return false;

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->logger->error('Brevo Client Error', [
                'to'      => $toEmail,
                'status'  => $e->getResponse()->getStatusCode(),
                'body'    => (string) $e->getResponse()->getBody(),
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Brevo Error', [
                'to'      => $toEmail,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ===================================================================
    //  Méthodes publiques (signatures inchangées)
    // ===================================================================

    public function sendProforma(Proforma $proforma, string $recipientEmail, ?string $message = null): bool
    {
        $template = $this->templateRepository->findDefaultByType('proforma');
        $settings = $this->settingsRepository->getOrCreateSettings();

        $subject = $this->processTemplate(
            $template?->getSubject() ?? 'Proforma {reference}',
            $proforma, $settings
        );

        $body = $this->processTemplate(
            $template?->getBodyHtml() ?? $this->getDefaultProformaBody(),
            $proforma, $settings
        );

        if ($message) {
            $body .= "\n\nMessage personnel:\n" . $message;
        }

        $pdfPath     = $this->pdfGenerator->generateProformaPdf($proforma);
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';
        $htmlBody    = $this->wrapInHtmlTemplate($body, "Proforma {$proforma->getReference()}", $companyName);

        return $this->send($recipientEmail, $subject, $htmlBody, $pdfPath, "proforma_{$proforma->getReference()}.pdf");
    }

    public function sendInvoice(Invoice $invoice, string $recipientEmail, ?string $message = null): bool
    {
        $template = $this->templateRepository->findDefaultByType('invoice');
        $settings = $this->settingsRepository->getOrCreateSettings();

        $subject = $this->processTemplate(
            $template?->getSubject() ?? 'Facture {reference}',
            $invoice, $settings
        );

        $body = $this->processTemplate(
            $template?->getBodyHtml() ?? $this->getDefaultInvoiceBody(),
            $invoice, $settings
        );

        if ($message) {
            $body .= "\n\nMessage personnel:\n" . $message;
        }

        $pdfPath     = $this->pdfGenerator->generateInvoicePdf($invoice);
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';
        $htmlBody    = $this->wrapInHtmlTemplate($body, "Facture {$invoice->getReference()}", $companyName);

        return $this->send($recipientEmail, $subject, $htmlBody, $pdfPath, "facture_{$invoice->getReference()}.pdf");
    }

    public function sendShareLink(DocumentShare $share): bool
    {
        $settings    = $this->settingsRepository->getOrCreateSettings();
        $document    = $share->getProforma() ?? $share->getInvoice();
        $docType     = $share->getProforma() ? 'proforma' : 'facture';
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';

        $subject  = "Lien de consultation - {$docType} {$document->getReference()}";
        $htmlBody = $this->buildShareEmailHtml($share, $docType, $companyName);

        return $this->send($share->getRecipientEmail(), $subject, $htmlBody);
    }

    public function sendDocumentShare(DocumentShare $share, ?string $personalMessage = null): bool
    {
        $settings    = $this->settingsRepository->getOrCreateSettings();
        $document    = $share->getProforma() ?? $share->getInvoice();
        $docType     = $share->getProforma() ? 'proforma' : 'facture';
        $companyName = $settings->getCompanyName() ?? 'KTC-Center';

        $subject  = ucfirst($docType) . " {$document->getReference()}";
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
     * Envoyer un document directement par email avec pièce jointe PDF optionnelle
     */
    public function sendDocumentEmail(
        string  $toEmail,
        string  $subject,
        string  $companyName,
        string  $clientName,
        string  $docType,
        string  $reference,
        float   $totalAmount,
        string  $currency,
        ?string $personalMessage = null,
        ?string $pdfContent = null,
        ?string $pdfFilename = null
    ): bool {
        $htmlBody = $this->buildDirectDocumentEmailHtml(
            $companyName, $clientName, $docType, $reference,
            $totalAmount, $currency, $personalMessage
        );

        return $this->sendWithPdfContent($toEmail, $subject, $htmlBody, $pdfContent, $pdfFilename);
    }

    // ===================================================================
    //  Helpers privés
    // ===================================================================

    private function processTemplate(string $template, $document, $settings): string
    {
        $replacements = [
            '{reference}'    => $document->getReference(),
            '{date}'         => $document->getIssueDate()?->format('d/m/Y') ?? '',
            '{total_ttc}'    => number_format($document->getTotalTTCFloat(), 0, ',', ' '),
            '{currency}'     => $settings->getCurrency() ?? 'FCFA',
            '{company_name}' => $settings->getCompanyName() ?? 'KTC-Center',
            '{client_name}'  => $document->getClient()?->getName() ?? '',
            '{client_email}' => $document->getClient()?->getEmail() ?? '',
        ];

        if ($document instanceof Invoice) {
            $replacements['{due_date}'] = $document->getDueDate()?->format('d/m/Y') ?? '';
        }
        if ($document instanceof Proforma) {
            $replacements['{validity_date}'] = $document->getValidUntil()?->format('d/m/Y') ?? '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function getDefaultProformaBody(): string
    {
        return "Bonjour {client_name},\n\n"
            . "Veuillez trouver ci-joint notre proforma N° {reference} d'un montant de {total_ttc} {currency}.\n\n"
            . "N'hésitez pas à nous contacter pour toute question.\n\n"
            . "Cordialement,\n{company_name}";
    }

    private function getDefaultInvoiceBody(): string
    {
        return "Bonjour {client_name},\n\n"
            . "Veuillez trouver ci-joint notre facture N° {reference} d'un montant de {total_ttc} {currency}.\n"
            . "Date d'échéance : {due_date}\n\n"
            . "N'hésitez pas à nous contacter pour toute question.\n\n"
            . "Cordialement,\n{company_name}";
    }

    private function wrapInHtmlTemplate(string $textContent, string $title, string $companyName): string
    {
        $htmlContent = nl2br(htmlspecialchars($textContent));
        $safeCompany = htmlspecialchars($companyName);
        $year = date('Y');

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:\'Segoe UI\',Roboto,Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;"><tr><td style="padding:30px 20px;">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin:0 auto;max-width:600px;">'
            . '<tr><td style="background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);padding:30px 40px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">' . htmlspecialchars($title) . '</h1>'
            . '<p style="color:#A3C4DC;margin:8px 0 0;font-size:13px;">' . $safeCompany . '</p>'
            . '</td></tr>'
            . '<tr><td style="background-color:#ffffff;padding:35px 40px;border:1px solid #e5e7eb;border-top:none;">'
            . '<div style="color:#374151;font-size:15px;line-height:1.6;">' . $htmlContent . '</div>'
            . '</td></tr>'
            . '<tr><td style="background-color:#f9fafb;padding:25px 40px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;text-align:center;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0;">&copy; ' . $year . ' ' . $safeCompany . '</p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }

    private function buildShareEmailHtml(DocumentShare $share, string $docType, string $companyName, ?string $personalMessage = null): string
    {
        $document     = $share->getProforma() ?? $share->getInvoice();
        $reference    = $document->getReference();
        $docTypeLabel = $docType === 'proforma' ? 'Proforma' : 'Facture';
        $emoji        = $docType === 'proforma' ? '&#x1F4CB;' : '&#x1F9FE;';
        $year         = date('Y');
        $expiresAt    = $share->getExpiresAt()?->format('d/m/Y à H:i') ?? 'N/A';
        $url          = $share->getShareUrl() ?? '#';

        $personalHtml = '';
        if ($personalMessage) {
            $safeMsg = nl2br(htmlspecialchars($personalMessage));
            $personalHtml = '<div style="background-color:#eff6ff;border-left:4px solid #2E86AB;border-radius:0 8px 8px 0;padding:16px 20px;margin:0 0 25px;">'
                . '<p style="color:#1e40af;font-size:13px;font-weight:600;margin:0 0 6px;">Message :</p>'
                . '<p style="color:#374151;font-size:14px;margin:0;line-height:1.6;">' . $safeMsg . '</p>'
                . '</div>';
        }

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . $docTypeLabel . ' ' . $reference . '</title></head>'
            . '<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;"><tr><td style="padding:30px 20px;">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin:0 auto;max-width:600px;">'
            . '<tr><td style="background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);padding:30px 40px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<div style="width:60px;height:60px;margin:0 auto 15px;background:rgba(255,255,255,0.15);border-radius:50%;line-height:60px;font-size:28px;">' . $emoji . '</div>'
            . '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">' . $docTypeLabel . ' ' . $reference . '</h1>'
            . '<p style="color:#A3C4DC;margin:8px 0 0;font-size:13px;">' . htmlspecialchars($companyName) . '</p>'
            . '</td></tr>'
            . '<tr><td style="background-color:#ffffff;padding:35px 40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">'
            . '<p style="color:#374151;font-size:16px;line-height:1.6;margin:0 0 20px;">Bonjour,</p>'
            . '<p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 25px;">Un document vous a été partagé. Cliquez sur le bouton ci-dessous pour le consulter :</p>'
            . $personalHtml
            . '<div style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:18px 22px;margin:0 0 25px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Type :</td><td style="color:#111827;font-size:13px;font-weight:600;text-align:right;padding:4px 0;">' . $docTypeLabel . '</td></tr>'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Référence :</td><td style="color:#111827;font-size:13px;font-weight:600;text-align:right;padding:4px 0;">' . $reference . '</td></tr>'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Expire le :</td><td style="color:#dc2626;font-size:13px;font-weight:600;text-align:right;padding:4px 0;">' . $expiresAt . '</td></tr>'
            . '</table></div>'
            . '<div style="text-align:center;margin:30px 0;">'
            . '<a href="' . $url . '" style="display:inline-block;background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:600;letter-spacing:0.3px;" target="_blank">&#x1F4C4; Consulter le document</a>'
            . '</div>'
            . '<div style="background-color:#f3f4f6;border-radius:8px;padding:14px 18px;margin-top:20px;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0 0 6px;">Si le bouton ne fonctionne pas, copiez-collez ce lien :</p>'
            . '<p style="color:#2563eb;font-size:12px;margin:0;word-break:break-all;"><a href="' . $url . '" style="color:#2563eb;text-decoration:underline;">' . $url . '</a></p>'
            . '</div>'
            . '</td></tr>'
            . '<tr><td style="background-color:#f9fafb;padding:25px 40px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;text-align:center;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0;">Cet email a été envoyé automatiquement par <strong>' . htmlspecialchars($companyName) . '</strong></p>'
            . '<p style="color:#9ca3af;font-size:11px;margin:8px 0 0;">&copy; ' . $year . ' ' . htmlspecialchars($companyName) . ' &mdash; Tous droits réservés</p>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private function buildDirectDocumentEmailHtml(
        string  $companyName,
        string  $clientName,
        string  $docType,
        string  $reference,
        float   $totalAmount,
        string  $currency,
        ?string $personalMessage = null
    ): string {
        $safeCompany   = htmlspecialchars($companyName);
        $safeClient    = htmlspecialchars($clientName);
        $safeRef       = htmlspecialchars($reference);
        $docTypeLabel  = $docType === 'proforma' ? 'Proforma' : 'Facture';
        $emoji         = $docType === 'proforma' ? '&#x1F4CB;' : '&#x1F9FE;';
        $year          = date('Y');
        $formattedAmt  = number_format($totalAmount, 0, ',', ' ') . ' ' . $currency;

        $personalHtml = '';
        if ($personalMessage) {
            $safeMsg = nl2br(htmlspecialchars($personalMessage));
            $personalHtml = '<div style="background-color:#eff6ff;border-left:4px solid #2E86AB;border-radius:0 8px 8px 0;padding:16px 20px;margin:0 0 25px;">'
                . '<p style="color:#1e40af;font-size:13px;font-weight:600;margin:0 0 6px;">Message :</p>'
                . '<p style="color:#374151;font-size:14px;margin:0;line-height:1.6;">' . $safeMsg . '</p>'
                . '</div>';
        }

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:\'Segoe UI\',Roboto,Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;"><tr><td style="padding:30px 20px;">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin:0 auto;max-width:600px;">'
            . '<tr><td style="background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);padding:30px 40px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<div style="width:60px;height:60px;margin:0 auto 15px;background:rgba(255,255,255,0.15);border-radius:50%;line-height:60px;font-size:28px;">' . $emoji . '</div>'
            . '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">' . $docTypeLabel . ' ' . $safeRef . '</h1>'
            . '<p style="color:#A3C4DC;margin:8px 0 0;font-size:13px;">' . $safeCompany . '</p>'
            . '</td></tr>'
            . '<tr><td style="background-color:#ffffff;padding:35px 40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">'
            . '<p style="color:#374151;font-size:16px;line-height:1.6;margin:0 0 20px;">Bonjour <strong>' . $safeClient . '</strong>,</p>'
            . '<p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 25px;">Veuillez trouver ci-joint votre ' . strtolower($docTypeLabel) . '.</p>'
            . $personalHtml
            . '<div style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:18px 22px;margin:0 0 25px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Type :</td><td style="color:#111827;font-size:13px;font-weight:600;text-align:right;padding:4px 0;">' . $docTypeLabel . '</td></tr>'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Référence :</td><td style="color:#111827;font-size:13px;font-weight:600;text-align:right;padding:4px 0;">' . $safeRef . '</td></tr>'
            . '<tr><td style="color:#6b7280;font-size:13px;padding:4px 0;">Montant TTC :</td><td style="color:#1E3A5F;font-size:15px;font-weight:700;text-align:right;padding:4px 0;">' . $formattedAmt . '</td></tr>'
            . '</table></div>'
            . '<div style="background-color:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:14px 18px;text-align:center;">'
            . '<p style="color:#065f46;font-size:13px;margin:0;">&#x1F4CE; Le document PDF est joint à cet email</p>'
            . '</div>'
            . '</td></tr>'
            . '<tr><td style="background-color:#f9fafb;padding:25px 40px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;text-align:center;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0;">Cet email a été envoyé par <strong>' . $safeCompany . '</strong></p>'
            . '<p style="color:#9ca3af;font-size:11px;margin:8px 0 0;">&copy; ' . $year . ' ' . $safeCompany . '</p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }
}