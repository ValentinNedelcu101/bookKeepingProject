<?php

namespace App\Controller\Api;

use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/invoices')]
final class InvoiceController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('', name: 'api_invoice_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $invoices = $this->em->getRepository(Invoice::class)->findBy(
            ['createdBy' => $this->getUser()]
        );

        return $this->json(array_map(fn(Invoice $i) => $this->serializeList($i), $invoices));
    }

    #[Route('/{id}', name: 'api_invoice_show', methods: ['GET'])]
    public function show(Invoice $invoice): JsonResponse
    {
        return $this->json($this->serializeDetail($invoice));
    }

    #[Route('', name: 'api_invoice_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['invoice_number']) || empty($data['client_id']) || empty($data['issue_date'])) {
            return $this->json(['error' => 'invoice_number, client_id and issue_date are required'], 400);
        }

        $client = $this->em->find(Client::class, $data['client_id']);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $invoice = new Invoice();
        $invoice->setInvoiceNumber($data['invoice_number']);
        $invoice->setClient($client);
        $invoice->setCreatedBy($this->getUser());
        $invoice->setIssueDate(new \DateTime($data['issue_date']));

        if (!empty($data['due_date'])) {
            $invoice->setDueDate(new \DateTime($data['due_date']));
        }
        if (!empty($data['notes'])) {
            $invoice->setNotes($data['notes']);
        }

        foreach ($data['items'] ?? [] as $itemData) {
            $item = new InvoiceItem();
            $item->setDescription($itemData['description']);
            $item->setQuantity((int) $itemData['quantity']);
            $item->setUnitPrice($itemData['unit_price']);
            $item->setTax($itemData['tax'] ?? null);
            $item->setLineTotal(
                bcmul($itemData['unit_price'], (string) $itemData['quantity'], 2)
            );
            $invoice->addItem($item);
        }

        $this->recalculate($invoice);

        $this->em->persist($invoice);
        $this->em->flush();

        return $this->json($this->serializeDetail($invoice), 201);
    }

    #[Route('/{id}', name: 'api_invoice_update', methods: ['PUT', 'PATCH'])]
    public function update(Invoice $invoice, Request $request): JsonResponse
    {
        if ($invoice->getStatus() !== 'draft') {
            return $this->json(['error' => 'Only draft invoices can be edited'], 422);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['issue_date'])) $invoice->setIssueDate(new \DateTime($data['issue_date']));
        if (!empty($data['due_date']))   $invoice->setDueDate(new \DateTime($data['due_date']));
        if (isset($data['notes']))       $invoice->setNotes($data['notes']);

        if (!empty($data['client_id'])) {
            $client = $this->em->find(Client::class, $data['client_id']);
            if (!$client) {
                return $this->json(['error' => 'Client not found'], 404);
            }
            $invoice->setClient($client);
        }

        if (isset($data['items'])) {
            foreach ($invoice->getItems() as $item) {
                $invoice->removeItem($item);
            }
            foreach ($data['items'] as $itemData) {
                $item = new InvoiceItem();
                $item->setDescription($itemData['description']);
                $item->setQuantity((int) $itemData['quantity']);
                $item->setUnitPrice($itemData['unit_price']);
                $item->setTax($itemData['tax'] ?? null);
                $item->setLineTotal(
                    bcmul($itemData['unit_price'], (string) $itemData['quantity'], 2)
                );
                $invoice->addItem($item);
            }
        }

        $this->recalculate($invoice);
        $this->em->flush();

        return $this->json($this->serializeDetail($invoice));
    }

    #[Route('/{id}/status', name: 'api_invoice_status', methods: ['PATCH'])]
    public function changeStatus(Invoice $invoice, Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        $allowed = ['draft', 'sent', 'paid', 'cancelled'];
        if (!in_array($status, $allowed)) {
            return $this->json(['error' => 'Invalid status. Allowed: ' . implode(', ', $allowed)], 400);
        }

        $invoice->setStatus($status);
        $this->em->flush();

        return $this->json(['status' => $invoice->getStatus()]);
    }

    #[Route('/{id}', name: 'api_invoice_delete', methods: ['DELETE'])]
    public function delete(Invoice $invoice): JsonResponse
    {
        if ($invoice->getStatus() !== 'draft') {
            return $this->json(['error' => 'Only draft invoices can be deleted'], 422);
        }

        $this->em->remove($invoice);
        $this->em->flush();

        return $this->json(null, 204);
    }

    #[Route('/{id}/pdf', name: 'api_invoice_pdf_store', methods: ['POST'])]
    public function storePdf(Invoice $invoice, Request $request, PdfService $pdfService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['html'])) {
            return $this->json(['error' => 'html is required'], 400);
        }

        $path = $pdfService->storeInvoicePdf($invoice, $data['html']);

        return $this->json(['pdf_path' => $path], 201);
    }

    #[Route('/{id}/pdf', name: 'api_invoice_pdf_get', methods: ['GET'])]
    public function getPdf(Invoice $invoice, PdfService $pdfService): Response
    {
        $path = $invoice->getPdfPath();

        if (!$path || $pdfService->isStale($path, $invoice->getUpdatedAt(), $invoice->getPdfGeneratedAt())) {
            return new Response('No PDF available. POST the HTML first.', 404);
        }

        return new Response(
            $pdfService->getStoredPdf($path),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $invoice->getInvoiceNumber() . '.pdf"',
            ]
        );
    }

    private function recalculate(Invoice $invoice): void
    {
        $subtotal = '0.00';
        foreach ($invoice->getItems() as $item) {
            $subtotal = bcadd($subtotal, $item->getLineTotal(), 2);
        }
        $taxTotal = bcmul($subtotal, bcdiv($invoice->getTaxTotal() ?? '0', '100', 4), 2);
        $invoice->setSubtotal($subtotal);
        $invoice->setTotal(bcadd($subtotal, $taxTotal, 2));
    }

    private function serializeList(Invoice $invoice): array
    {
        return [
            'id'             => $invoice->getId(),
            'invoice_number' => $invoice->getInvoiceNumber(),
            'status'         => $invoice->getStatus(),
            'client'         => $invoice->getClient()->getName(),
            'issue_date'     => $invoice->getIssueDate()?->format('Y-m-d'),
            'due_date'       => $invoice->getDueDate()?->format('Y-m-d'),
            'total'          => $invoice->getTotal(),
        ];
    }

    private function serializeDetail(Invoice $invoice): array
    {
        return [
            'id'               => $invoice->getId(),
            'invoice_number'   => $invoice->getInvoiceNumber(),
            'status'           => $invoice->getStatus(),
            'client'           => [
                'id'   => $invoice->getClient()->getId(),
                'name' => $invoice->getClient()->getName(),
            ],
            'issue_date'       => $invoice->getIssueDate()?->format('Y-m-d'),
            'due_date'         => $invoice->getDueDate()?->format('Y-m-d'),
            'subtotal'         => $invoice->getSubtotal(),
            'tax_total'        => $invoice->getTaxTotal(),
            'total'            => $invoice->getTotal(),
            'notes'            => $invoice->getNotes(),
            'pdf_generated_at' => $invoice->getPdfGeneratedAt()?->format('Y-m-d H:i:s'),
            'updated_at'       => $invoice->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'items'            => array_map(fn(InvoiceItem $item) => [
                'id'          => $item->getId(),
                'description' => $item->getDescription(),
                'quantity'    => $item->getQuantity(),
                'unit_price'  => $item->getUnitPrice(),
                'tax'         => $item->getTax(),
                'line_total'  => $item->getLineTotal(),
            ], $invoice->getItems()->toArray()),
        ];
    }
}
