<?php

namespace App\Controller\Api;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clients')]
final class ClientController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('', name: 'api_client_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $clients = $this->em->getRepository(Client::class)->findAll();

        return $this->json(array_map(fn(Client $c) => $this->serialize($c), $clients));
    }

    #[Route('/{id}', name: 'api_client_show', methods: ['GET'])]
    public function show(Client $client): JsonResponse
    {
        return $this->json($this->serialize($client));
    }

    #[Route('', name: 'api_client_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'name is required'], 400);
        }

        $client = new Client();
        $this->hydrate($client, $data);
        $client->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($client);
        $this->em->flush();

        return $this->json($this->serialize($client), 201);
    }

    #[Route('/{id}', name: 'api_client_update', methods: ['PUT', 'PATCH'])]
    public function update(Client $client, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $this->hydrate($client, $data);
        $this->em->flush();

        return $this->json($this->serialize($client));
    }

    #[Route('/{id}', name: 'api_client_delete', methods: ['DELETE'])]
    public function delete(Client $client): JsonResponse
    {
        $this->em->remove($client);
        $this->em->flush();

        return $this->json(null, 204);
    }

    private function hydrate(Client $client, array $data): void
    {
        if (isset($data['name']))            $client->setName($data['name']);
        if (isset($data['contact_email']))   $client->setContactEmail($data['contact_email']);
        if (isset($data['phone']))           $client->setPhone($data['phone']);
        if (isset($data['billing_address'])) $client->setBillingAddress($data['billing_address']);
        if (isset($data['tax_number']))      $client->setTaxNumber($data['tax_number']);
    }

    private function serialize(Client $client): array
    {
        return [
            'id'              => $client->getId(),
            'name'            => $client->getName(),
            'contact_email'   => $client->getContactEmail(),
            'phone'           => $client->getPhone(),
            'billing_address' => $client->getBillingAddress(),
            'tax_number'      => $client->getTaxNumber(),
            'created_at'      => $client->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
