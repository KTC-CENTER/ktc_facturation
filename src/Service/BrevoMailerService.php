<?php

namespace App\Service;

use App\Entity\Proforma;
use App\Entity\Invoice;
use App\Entity\EmailTemplate;
use App\Entity\DocumentShare;
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

    /**
     * Envoie une proforma par email
     */
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
            $template?->getBody() ?? $this->getDefaultProformaBody(),
            $proforma,
            $settings
        );

        if ($message) {
            $body .= "\n\nMessage personnel:\n" . $message;
        }

        // Générer le PDF
        $pdfPath = $this->pdfGenerator->generateProformaPdf($proforma);

        return $this->send(
            $recipientEmail,
            $subject,
            $body,
            $pdfPath,
            "proforma_{$proforma->getReference()}.pdf"
        );
    }

    /**
     * Envoie une facture par email
     */
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
            $template?->getBody() ?? $this->getDefaultInvoiceBody(),
            $invoice,
            $settings
        );

        if ($message) {
            $body .= "\n\nMessage personnel:\n" . $message;
        }

        // Générer le PDF
        $pdfPath = $this->pdfGenerator->generateInvoicePdf($invoice);

        return $this->send(
            $recipientEmail,
            $subject,
            $body,
            $pdfPath,
            "facture_{$invoice->getReference()}.pdf"
        );
    }

    /**
     * Envoie un lien de partage
     */
    public function sendShareLink(DocumentShare $share): bool
    {
        $template = $this->templateRepository->findDefaultByType('share');
        $settings = $this->settingsRepository->getOrCreateSettings();

        $document = $share->getProforma() ?? $share->getInvoice();
        $docType = $share->getProforma() ? 'proforma' : 'facture';

        $subject = "Lien de consultation - {$docType} {$document->getReference()}";
        
        $body = $this->getDefaultShareBody($share, $docType);

        return $this->send($share->getRecipientEmail(), $subject, $body);
    }

    /**
     * Envoie un email générique
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $attachmentPath = null,
        ?string $attachmentName = null
    ): bool {
        try {
            $email = new SendSmtpEmail();
            $email->setSubject($subject);
            $email->setHtmlContent(nl2br($body));
            $email->setTextContent(strip_tags($body));
            
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

    /**
     * Remplace les placeholders dans le template
     */
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
        return <<<BODY
Bonjour {client_name},

Veuillez trouver ci-joint notre proforma n° {reference} d'un montant de {total_ttc} {currency}.

Cette proforma est valable jusqu'au {valid_until}.

N'hésitez pas à nous contacter pour toute question.

Cordialement,
{company_name}
BODY;
    }

    private function getDefaultInvoiceBody(): string
    {
        return <<<BODY
Bonjour {client_name},

Veuillez trouver ci-joint notre facture n° {reference} d'un montant de {total_ttc} {currency}.

Date d'échéance : {due_date}

Nous vous remercions pour votre confiance.

Cordialement,
{company_name}
BODY;
    }

    private function getDefaultShareBody(DocumentShare $share, string $docType): string
    {
        $url = $share->getShareUrl();
        $expiresAt = $share->getExpiresAt()->format('d/m/Y à H:i');

        return <<<BODY
Bonjour,

Un lien de consultation vous a été partagé pour une {$docType}.

Cliquez sur le lien suivant pour consulter le document :
{$url}

Ce lien expire le {$expiresAt}.

Cordialement
BODY;
    }
}
