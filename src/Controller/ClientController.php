<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients')]
#[IsGranted('ROLE_VIEWER')]
class ClientController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClientRepository $clientRepository
    ) {}

    #[Route('', name: 'app_client_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $showArchived = $request->query->getBoolean('archived', false);

        $queryBuilder = $this->clientRepository->createQueryBuilder('c');

        if (!$showArchived) {
            $queryBuilder->andWhere('c.isArchived = :archived')
                ->setParameter('archived', false);
        }

        if ($search) {
            $queryBuilder->andWhere('c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $queryBuilder->orderBy('c.name', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('client/index.html.twig', [
            'clients' => $pagination,
            'search' => $search,
            'showArchived' => $showArchived,
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function new(Request $request): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($client);
            $this->entityManager->flush();

            $this->addFlash('success', 'Client créé avec succès.');
            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function edit(Request $request, Client $client): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Client modifié avec succès.');
            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/archive', name: 'app_client_archive', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function archive(Request $request, Client $client): Response
    {
        if ($this->isCsrfTokenValid('archive' . $client->getId(), $request->request->get('_token'))) {
            $client->setIsArchived(!$client->isArchived());
            $this->entityManager->flush();

            $message = $client->isArchived() ? 'Client archivé.' : 'Client restauré.';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_client_index');
    }

    #[Route('/{id}/delete', name: 'app_client_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Client $client): Response
    {
        if ($this->isCsrfTokenValid('delete' . $client->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des documents liés
            if ($client->getProformas()->count() > 0 || $client->getInvoices()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce client car il a des documents liés. Archivez-le plutôt.');
                return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
            }

            $this->entityManager->remove($client);
            $this->entityManager->flush();

            $this->addFlash('success', 'Client supprimé avec succès.');
        }

        return $this->redirectToRoute('app_client_index');
    }

    #[Route('/{id}/documents', name: 'app_client_documents', methods: ['GET'])]
    public function documents(Client $client, Request $request, PaginatorInterface $paginator): Response
    {
        $type = $request->query->get('type', 'all');

        return $this->render('client/documents.html.twig', [
            'client' => $client,
            'type' => $type,
        ]);
    }
}
