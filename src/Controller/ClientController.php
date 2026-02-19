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

    #[Route('/{id}/email', name: 'app_client_email', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function sendEmail(Request $request, Client $client, \App\Service\BrevoMailerService $mailer, \App\Repository\CompanySettingsRepository $settingsRepo): Response
    {
        if (!$client->getEmail()) {
            $this->addFlash('error', 'Ce client n\'a pas d\'adresse email.');
            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject', '');
            $message = $request->request->get('message', '');

            if (empty($subject) || empty($message)) {
                $this->addFlash('error', 'Le sujet et le message sont obligatoires.');
            } else {
                $settings = $settingsRepo->getOrCreateSettings();
                $companyName = $settings->getCompanyName() ?? 'KTC-Center';

                $htmlBody = $this->buildClientEmailHtml($companyName, $client->getName(), $subject, $message);
                $success = $mailer->send($client->getEmail(), $subject, $htmlBody);

                if ($success) {
                    $this->addFlash('success', 'Email envoyé avec succès à ' . $client->getEmail());
                    return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email.');
                }
            }
        }

        return $this->render('client/email.html.twig', [
            'client' => $client,
        ]);
    }

    private function buildClientEmailHtml(string $companyName, string $clientName, string $subject, string $message): string
    {
        $safeCompany = htmlspecialchars($companyName);
        $safeClient = htmlspecialchars($clientName);
        $safeSubject = htmlspecialchars($subject);
        $htmlMessage = nl2br(htmlspecialchars($message));
        $year = date('Y');

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:\'Segoe UI\',Roboto,Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;"><tr><td style="padding:30px 20px;">'
            . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="margin:0 auto;max-width:600px;">'
            . '<tr><td style="background:linear-gradient(135deg,#1E3A5F 0%,#2E86AB 100%);padding:30px 40px;border-radius:12px 12px 0 0;text-align:center;">'
            . '<h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">' . $safeCompany . '</h1>'
            . '<p style="color:#A3C4DC;margin:8px 0 0;font-size:13px;">' . $safeSubject . '</p></td></tr>'
            . '<tr><td style="background-color:#ffffff;padding:35px 40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">'
            . '<p style="color:#374151;font-size:16px;line-height:1.6;margin:0 0 20px;">Bonjour <strong>' . $safeClient . '</strong>,</p>'
            . '<div style="color:#374151;font-size:15px;line-height:1.7;">' . $htmlMessage . '</div>'
            . '</td></tr>'
            . '<tr><td style="background-color:#f9fafb;padding:25px 40px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;text-align:center;">'
            . '<p style="color:#6b7280;font-size:12px;margin:0;">' . $safeCompany . '</p>'
            . '<p style="color:#9ca3af;font-size:11px;margin:8px 0 0;">&copy; ' . $year . ' ' . $safeCompany . '</p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }
}
