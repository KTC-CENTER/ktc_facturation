<?php

namespace App\Service;

use App\Entity\Proforma;
use App\Entity\Invoice;
use App\Entity\CompanySettings;
use App\Repository\CompanySettingsRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class PdfGeneratorService
{
    private CompanySettingsRepository $settingsRepository;
    private Environment $twig;
    private string $projectDir;
    private string $uploadsDir;

    public function __construct(
        CompanySettingsRepository $settingsRepository,
        Environment $twig,
        KernelInterface $kernel,
        string $uploadsDirectory
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->twig = $twig;
        $this->projectDir = $kernel->getProjectDir();
        $this->uploadsDir = $uploadsDirectory;
    }

    /**
     * Génère le PDF d'une proforma
     * @param bool $saveToFile Si true, sauvegarde et retourne le chemin. Si false, retourne le contenu brut.
     */
    public function generateProformaPdf(Proforma $proforma, bool $saveToFile = true): string
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $logoBase64 = $this->getLogoBase64($settings);
        
        $html = $this->twig->render('pdf/proforma.html.twig', [
            'proforma' => $proforma,
            'settings' => $settings,
            'logo_base64' => $logoBase64,
        ]);

        return $this->generatePdf($html, "proforma_{$proforma->getReference()}.pdf", $saveToFile);
    }

    /**
     * Génère le PDF d'une facture
     * @param bool $saveToFile Si true, sauvegarde et retourne le chemin. Si false, retourne le contenu brut.
     */
    public function generateInvoicePdf(Invoice $invoice, bool $saveToFile = true): string
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $logoBase64 = $this->getLogoBase64($settings);
        
        $html = $this->twig->render('pdf/invoice.html.twig', [
            'invoice' => $invoice,
            'settings' => $settings,
            'logo_base64' => $logoBase64,
        ]);

        return $this->generatePdf($html, "facture_{$invoice->getReference()}.pdf", $saveToFile);
    }

    /**
     * Génère un aperçu HTML d'une proforma (pour affichage navigateur)
     */
    public function generateProformaPreview(Proforma $proforma): string
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $logoBase64 = $this->getLogoBase64($settings);
        
        return $this->twig->render('pdf/proforma.html.twig', [
            'proforma' => $proforma,
            'settings' => $settings,
            'logo_base64' => $logoBase64,
        ]);
    }

    public function generateInvoicePreview(Invoice $invoice): string
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $logoBase64 = $this->getLogoBase64($settings);
        
        return $this->twig->render('pdf/invoice.html.twig', [
            'invoice' => $invoice,
            'settings' => $settings,
            'logo_base64' => $logoBase64,
        ]);
    }

    /**
     * Convertit le logo de l'entreprise en base64 pour l'intégrer dans les PDFs
     */
    private function getLogoBase64($settings): ?string
    {
        if (!$settings->getLogoPath()) {
            return null;
        }

        $logoPath = $this->uploadsDir . '/logo/' . $settings->getLogoPath();
        if (!file_exists($logoPath)) {
            return null;
        }

        $mimeType = mime_content_type($logoPath) ?: 'image/png';
        $data = file_get_contents($logoPath);
        return 'data:' . $mimeType . ';base64,' . base64_encode($data);
    }

    private function generatePdf(string $html, string $filename, bool $saveToFile = true): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', $this->projectDir);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        if (!$saveToFile) {
            return $output;
        }

        // Sauvegarder le PDF
        $pdfDir = $this->uploadsDir . '/pdf';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }

        $filepath = $pdfDir . '/' . $filename;
        file_put_contents($filepath, $output);

        return $filepath;
    }

    /**
     * Retourne le contenu binaire du PDF
     */
    public function getPdfContent(string $filepath): string
    {
        if (!file_exists($filepath)) {
            throw new \RuntimeException("Le fichier PDF n'existe pas: {$filepath}");
        }

        return file_get_contents($filepath);
    }

    /**
     * Génère un PDF de rapport
     */
    public function generateReportPdf(string $template, array $data, string $filename): string
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $data['settings'] = $settings;
        
        $html = $this->twig->render($template, $data);

        return $this->generatePdf($html, $filename);
    }
}
