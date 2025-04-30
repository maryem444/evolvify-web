<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Generate a PDF from raw HTML content
     * 
     * @param string $html The HTML content to convert to PDF
     * @param string $filename The filename for the generated PDF
     * @param array $options Options for PDF generation
     * @return string The binary PDF content
     */
    public function generatePdf(string $html, string $filename = 'document.pdf', array $options = []): string
    {
        // Configure Dompdf options
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', $options['isRemoteEnabled'] ?? true);
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isPhpEnabled', false); // Security best practice
        $pdfOptions->set('isFontSubsettingEnabled', true);
        
        // Set additional options if specified
        if (isset($options['options']) && is_array($options['options'])) {
            foreach ($options['options'] as $key => $value) {
                if (method_exists($pdfOptions, 'set')) {
                    $pdfOptions->set($key, $value);
                }
            }
        }
        
        // Initialize Dompdf with options
        $dompdf = new Dompdf($pdfOptions);
        
        // Load HTML content
        $dompdf->loadHtml($html, 'UTF-8');
        
        // Set paper size and orientation
        $paperSize = $options['paperSize'] ?? 'A4';
        $paperOrientation = $options['paperOrientation'] ?? 'portrait';
        $dompdf->setPaper($paperSize, $paperOrientation);
        
        // Render the PDF
        $dompdf->render();
        
        // Return the generated PDF as binary content
        return $dompdf->output();
    }

    /**
     * Generate a PDF from a Twig template
     * 
     * @param string $templatePath The path to the Twig template
     * @param array $templateParams Parameters to pass to the template
     * @param string $filename The filename for the generated PDF
     * @param array $options Options for PDF generation
     * @return string The binary PDF content
     */
    public function generatePdfFromTemplate(string $templatePath, array $templateParams = [], string $filename = 'document.pdf', array $options = []): string
    {
        // Set PDF flag to tell the template to use PDF-specific layout
        $templateParams['isPdf'] = true;
        
        try {
            // Render the template to HTML
            $html = $this->twig->render($templatePath, $templateParams);
            
            // Generate PDF from HTML
            return $this->generatePdf($html, $filename, $options);
        } catch (\Exception $e) {
            // Log the error
            error_log('PDF Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }
}