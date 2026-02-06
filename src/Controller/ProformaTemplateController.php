<?php

namespace App\Controller;

use App\Entity\ProformaTemplate;
use App\Entity\TemplateItem;
use App\Repository\ProformaTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/proforma-templates')]
#[IsGranted('ROLE_COMMERCIAL')]
class ProformaTemplateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProformaTemplateRepository $templateRepository
    ) {}

    #[Route('', name: 'app_proforma_template_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');

        $qb = $this->templateRepository->createQueryBuilder('t')
            ->where('t.isActive = true')
            ->orderBy('t.name', 'ASC');

        if ($search) {
            $qb->andWhere('t.name LIKE :search OR t.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $templates = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('proforma_template/index.html.twig', [
            'templates' => $templates,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_proforma_template_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $template = new ProformaTemplate();
            $template->setName($request->request->get('name'));
            $template->setDescription($request->request->get('description'));
            $template->setConditions($request->request->get('conditions'));
            $template->setValidityDays((int)$request->request->get('validityDays', 30));
            $template->setTaxRate($request->request->get('taxRate', '19.25'));
            $template->setIsActive(true);

            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $this->addFlash('success', 'Modèle créé avec succès.');
            return $this->redirectToRoute('app_proforma_template_index');
        }

        return $this->render('proforma_template/new.html.twig');
    }

    #[Route('/{id}', name: 'app_proforma_template_show', methods: ['GET'])]
    public function show(ProformaTemplate $template): Response
    {
        return $this->render('proforma_template/show.html.twig', [
            'template' => $template,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_proforma_template_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, ProformaTemplate $template): Response
    {
        if ($request->isMethod('POST')) {
            $template->setName($request->request->get('name'));
            $template->setDescription($request->request->get('description'));
            $template->setConditions($request->request->get('conditions'));
            $template->setValidityDays((int)$request->request->get('validityDays', 30));
            $template->setTaxRate($request->request->get('taxRate', '19.25'));

            $this->entityManager->flush();

            $this->addFlash('success', 'Modèle modifié avec succès.');
            return $this->redirectToRoute('app_proforma_template_show', ['id' => $template->getId()]);
        }

        return $this->render('proforma_template/edit.html.twig', [
            'template' => $template,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_proforma_template_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, ProformaTemplate $template): Response
    {
        if ($this->isCsrfTokenValid('delete' . $template->getId(), $request->request->get('_token'))) {
            // Désactiver plutôt que supprimer
            $template->setIsActive(false);
            $this->entityManager->flush();
            $this->addFlash('success', 'Modèle supprimé.');
        }

        return $this->redirectToRoute('app_proforma_template_index');
    }

    #[Route('/{id}/use', name: 'app_proforma_template_use', methods: ['GET'])]
    public function useTemplate(ProformaTemplate $template): Response
    {
        return $this->redirectToRoute('app_proforma_new', ['template' => $template->getId()]);
    }
}
