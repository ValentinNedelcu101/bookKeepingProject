<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Quotation;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private string $storageDir;

    public function __construct(
        private EntityManagerInterface $em,
        string $projectDir
    ) {
        $this->storageDir = $projectDir . '/var/pdfs';
    }

    public function storeInvoicePdf(Invoice $invoice, string $html): string
    {
        $content = $this->htmlToPdf($html);
        $path    = $this->store($content, 'invoices', $invoice->getInvoiceNumber());

        $invoice->setPdfPath($path);
        $invoice->setPdfGeneratedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $path;
    }

    public function storeQuotationPdf(Quotation $quotation, string $html): string
    {
        $content = $this->htmlToPdf($html);
        $path    = $this->store($content, 'quotations', $quotation->getQuotationNumber());

        $quotation->setPdfPath($path);
        $quotation->setPdfGeneratedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $path;
    }

    public function getStoredPdf(string $path): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('PDF file not found.');
        }

        return file_get_contents($path);
    }

    public function isStale(string $pdfPath, ?\DateTimeImmutable $updatedAt, ?\DateTimeImmutable $generatedAt): bool
    {
        if (!file_exists($pdfPath)) {
            return true;
        }

        if ($updatedAt && $generatedAt && $updatedAt > $generatedAt) {
            return true;
        }

        return false;
    }

    private function htmlToPdf(string $html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function store(string $content, string $subfolder, string $filename): string
    {
        $dir = $this->storageDir . '/' . $subfolder;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $filename . '.pdf';
        file_put_contents($path, $content);

        return $path;
    }
}
