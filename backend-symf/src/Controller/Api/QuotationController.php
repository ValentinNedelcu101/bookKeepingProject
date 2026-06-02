<?php

namespace App\Controller\Api;

use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Quotation;
use App\Entity\QuotationItem;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/quotations')]
final class QuotationController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('', name: 'api_quotation_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $quotations = $this->em->getRepository(Quotation::class)->findBy(
            ['createdBy' => $this->getUser()]
        );

        return $this->json(array_map(fn(Quotation $q) => $this->serializeList($q), $quotations));
    }

    #[Route('/{id}', name: 'api_quotation_show', methods: ['GET'])]
    public function show(Quotation $quotation): JsonResponse
    {
        return $this->json($this->serializeDetail($quotation));
    }

    #[Route('', name: 'api_quotation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['quotation_number']) || empty($data['client_id']) || empty($data['issue_date'])) {
            return $this->json(['error' => 'quotation_number, client_id and issue_date are required'], 400);
        }

        $client = $this->em->find(Client::class, $data['client_id']);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $quotation = new Quotation();
        $quotation->setQuotationNumber($data['quotation_number']);
        $quotation->setClient($client);
        $quotation->setCreatedBy($this->getUser());
        $quotation->setIssueDate(new \DateTime($data['issue_date']));

        if (!empty($data['valid_until'])) {
            $quotation->setValidUntil(new \DateTime($data['valid_until']));
        }
        if (!empty($data['notes'])) {
            $quotation->setNotes($data['notes']);
        }

        foreach ($data['items'] ?? [] as $itemData) {
            $item = new QuotationItem();
            $item->setDescription($itemData['description']);
            $item->setQuantity((int) $itemData['quantity']);
            $item->setUnitPrice($itemData['unit_price']);
            $item->setTaxRate($itemData['tax_rate'] ?? null);
            $item->setLineTotal(
                bcmul($itemData['unit_price'], (string) $itemData['quantity'], 2)
            );
            $quotation->addQuotationItem($item);
        }

        $this->recalculate($quotation);

        $this->em->persist($quotation);
        $this->em->flush();

        return $this->json($this->serializeDetail($quotation), 201);
    }

    #[Route('/{id}', name: 'api_quotation_update', methods: ['PUT', 'PATCH'])]
    public function update(Quotation $quotation, Request $request): JsonResponse
    {
        if ($quotation->getStatus() !== 'draft') {
            return $this->json(['error' => 'Only draft quotations can be edited'], 422);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['issue_date']))  $quotation->setIssueDate(new \DateTime($data['issue_date']));
        if (!empty($data['valid_until'])) $quotation->setValidUntil(new \DateTime($data['valid_until']));
        if (isset($data['notes']))        $quotation->setNotes($data['notes']);

        if (!empty($data['client_id'])) {
            $client = $this->em->find(Client::class, $data['client_id']);
            if (!$client) {
                return $this->json(['error' => 'Client not found'], 404);
            }
            $quotation->setClient($client);
        }

        if (isset($data['items'])) {
            foreach ($quotation->getQuotationItems() as $item) {
                $quotation->removeQuotationItem($item);
            }
            foreach ($data['items'] as $itemData) {
                $item = new QuotationItem();
                $item->setDescription($itemData['description']);
                $item->setQuantity((int) $itemData['quantity']);
                $item->setUnitPrice($itemData['unit_price']);
                $item->setTaxRate($itemData['tax_rate'] ?? null);
                $item->setLineTotal(
                    bcmul($itemData['unit_price'], (string) $itemData['quantity'], 2)
                );
                $quotation->addQuotationItem($item);
            }
        }

        $this->recalculate($quotation);
        $this->em->flush();

        return $this->json($this->serializeDetail($quotation));
    }

    #[Route('/{id}/status', name: 'api_quotation_status', methods: ['PATCH'])]
    public function changeStatus(Quotation $quotation, Request $request): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $status  = $data['status'] ?? null;
        $allowed = ['draft', 'sent', 'accepted', 'rejected'];

        if (!in_array($status, $allowed)) {
            return $this->json(['error' => 'Invalid status. Allowed: ' . implode(', ', $allowed)], 400);
        }

        $quotation->setStatus($status);
        $this->em->flush();

        return $this->json(['status' => $quotation->getStatus()]);
    }

    #[Route('/{id}/convert', name: 'api_quotation_convert', methods: ['PATCH'])]
    public function convert(Quotation $quotation): JsonResponse
    {
        if ($quotation->getStatus() !== 'accepted') {
            return $this->json(['error' => 'Only accepted quotations can be converted to invoices'], 422);
        }

        $invoice = new Invoice();
        $invoice->setInvoiceNumber('INV-' . substr($quotation->getQuotationNumber(), 6));
        $invoice->setClient($quotation->getClient());
        $invoice->setCreatedBy($this->getUser());
        $invoice->setIssueDate(new \DateTime());
        $invoice->setNotes($quotation->getNotes());
        $invoice->setSubtotal($quotation->getSubtotal());
        $invoice->setTaxTotal($quotation->getTaxTotal());
        $invoice->setTotal($quotation->getTotal());

        foreach ($quotation->getQuotationItems() as $qItem) {
            $item = new InvoiceItem();
            $item->setDescription($qItem->getDescription());
            $item->setQuantity($qItem->getQuantity());
            $item->setUnitPrice($qItem->getUnitPrice());
            $item->setTax($qItem->getTaxRate());
            $item->setLineTotal($qItem->getLineTotal());
            $invoice->addItem($item);
        }

        $quotation->setStatus('converted');

        $this->em->persist($invoice);
        $this->em->flush();

        return $this->json($this->serializeDetail($quotation) + ['invoice_id' => $invoice->getId()], 201);
    }

    #[Route('/{id}', name: 'api_quotation_delete', methods: ['DELETE'])]
    public function delete(Quotation $quotation): JsonResponse
    {
        if ($quotation->getStatus() !== 'draft') {
            return $this->json(['error' => 'Only draft quotations can be deleted'], 422);
        }

        $this->em->remove($quotation);
        $this->em->flush();

        return $this->json(null, 204);
    }

    #[Route('/{id}/pdf', name: 'api_quotation_pdf_store', methods: ['POST'])]
    public function storePdf(Quotation $quotation, Request $request, PdfService $pdfService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['html'])) {
            return $this->json(['error' => 'html is required'], 400);
        }

        $path = $pdfService->storeQuotationPdf($quotation, $data['html']);

        return $this->json(['pdf_path' => $path], 201);
    }

    #[Route('/{id}/pdf', name: 'api_quotation_pdf_get', methods: ['GET'])]
    public function getPdf(Quotation $quotation, PdfService $pdfService): Response
    {
        $path = $quotation->getPdfPath();

        if (!$path || $pdfService->isStale($path, $quotation->getUpdatedAt(), $quotation->getPdfGeneratedAt())) {
            return new Response('No PDF available. POST the HTML first.', 404);
        }

        return new Response(
            $pdfService->getStoredPdf($path),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $quotation->getQuotationNumber() . '.pdf"',
            ]
        );
    }

    private function recalculate(Quotation $quotation): void
    {
        $subtotal = '0.00';
        foreach ($quotation->getQuotationItems() as $item) {
            $subtotal = bcadd($subtotal, $item->getLineTotal(), 2);
        }
        $taxTotal = bcmul($subtotal, bcdiv($quotation->getTaxTotal() ?? '0', '100', 4), 2);
        $quotation->setSubtotal($subtotal);
        $quotation->setTotal(bcadd($subtotal, $taxTotal, 2));
    }

    private function serializeList(Quotation $quotation): array
    {
        return [
            'id'               => $quotation->getId(),
            'quotation_number' => $quotation->getQuotationNumber(),
            'status'           => $quotation->getStatus(),
            'client'           => $quotation->getClient()->getName(),
            'issue_date'       => $quotation->getIssueDate()?->format('Y-m-d'),
            'valid_until'      => $quotation->getValidUntil()?->format('Y-m-d'),
            'total'            => $quotation->getTotal(),
        ];
    }

    private function serializeDetail(Quotation $quotation): array
    {
        return [
            'id'               => $quotation->getId(),
            'quotation_number' => $quotation->getQuotationNumber(),
            'status'           => $quotation->getStatus(),
            'client'           => [
                'id'   => $quotation->getClient()->getId(),
                'name' => $quotation->getClient()->getName(),
            ],
            'issue_date'       => $quotation->getIssueDate()?->format('Y-m-d'),
            'valid_until'      => $quotation->getValidUntil()?->format('Y-m-d'),
            'subtotal'         => $quotation->getSubtotal(),
            'tax_total'        => $quotation->getTaxTotal(),
            'total'            => $quotation->getTotal(),
            'notes'            => $quotation->getNotes(),
            'pdf_generated_at' => $quotation->getPdfGeneratedAt()?->format('Y-m-d H:i:s'),
            'updated_at'       => $quotation->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'items'            => array_map(fn(QuotationItem $item) => [
                'id'          => $item->getId(),
                'description' => $item->getDescription(),
                'quantity'    => $item->getQuantity(),
                'unit_price'  => $item->getUnitPrice(),
                'tax_rate'    => $item->getTaxRate(),
                'line_total'  => $item->getLineTotal(),
            ], $quotation->getQuotationItems()->toArray()),
        ];
    }
}
