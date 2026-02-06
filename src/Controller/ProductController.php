<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ReferenceGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
#[IsGranted('ROLE_VIEWER')]
class ProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private ReferenceGeneratorService $referenceGenerator
    ) {}

    #[Route('', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $type = $request->query->get('type', '');
        $showInactive = $request->query->getBoolean('inactive', false);

        $queryBuilder = $this->productRepository->createQueryBuilder('p');

        if (!$showInactive) {
            $queryBuilder->andWhere('p.isActive = :active')
                ->setParameter('active', true);
        }

        if ($search) {
            $queryBuilder->andWhere('p.name LIKE :search OR p.code LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($type && in_array($type, array_keys(Product::TYPES))) {
            $queryBuilder->andWhere('p.type = :type')
                ->setParameter('type', $type);
        }

        $queryBuilder->orderBy('p.name', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('product/index.html.twig', [
            'products' => $pagination,
            'search' => $search,
            'type' => $type,
            'showInactive' => $showInactive,
            'types' => Product::TYPES,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer le code si non fourni
            if (!$product->getCode()) {
                $product->setCode($this->referenceGenerator->generateProductCode($product->getType()));
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_product_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function toggle(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $product->getId(), $request->request->get('_token'))) {
            $product->setIsActive(!$product->isActive());
            $this->entityManager->flush();

            $message = $product->isActive() ? 'Produit activé.' : 'Produit désactivé.';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des lignes de documents liées
            if ($product->getDocumentItems()->count() > 0 || $product->getTemplateItems()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce produit car il est utilisé dans des documents. Désactivez-le plutôt.');
                return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
            }

            $this->entityManager->remove($product);
            $this->entityManager->flush();

            $this->addFlash('success', 'Produit supprimé avec succès.');
        }

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/export/csv', name: 'app_product_export', methods: ['GET'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function export(): StreamedResponse
    {
        $products = $this->productRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        $response = new StreamedResponse(function () use ($products) {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->setDelimiter(';');
            
            // En-têtes
            $csv->insertOne([
                'Code', 'Nom', 'Type', 'Description', 'Prix unitaire', 'Unité',
                'Version', 'Type licence', 'Durée licence (mois)', 'Max utilisateurs',
                'Marque', 'Modèle', 'Garantie (mois)', 'Durée service (h)'
            ]);

            foreach ($products as $product) {
                $csv->insertOne([
                    $product->getCode(),
                    $product->getName(),
                    $product->getType(),
                    $product->getDescription(),
                    $product->getUnitPriceFloat(),
                    $product->getUnit(),
                    $product->getVersion(),
                    $product->getLicenseType(),
                    $product->getLicenseDuration(),
                    $product->getMaxUsers(),
                    $product->getBrand(),
                    $product->getModel(),
                    $product->getWarrantyMonths(),
                    $product->getDurationHours(),
                ]);
            }
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="produits_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    #[Route('/export/pdf', name: 'app_product_export_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function exportPdf(\App\Service\PdfGeneratorService $pdfService): Response
    {
        $products = $this->productRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        $pdfPath = $pdfService->generateReportPdf('pdf/products_list.html.twig', [
            'products' => $products,
            'date' => new \DateTime(),
        ], 'catalogue_produits_' . date('Y-m-d') . '.pdf');

        return new Response(
            file_get_contents($pdfPath),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="catalogue_produits_' . date('Y-m-d') . '.pdf"',
            ]
        );
    }

    #[Route('/import/csv', name: 'app_product_import', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function import(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('csv_file');

            if (!$file) {
                $this->addFlash('error', 'Veuillez sélectionner un fichier CSV.');
                return $this->redirectToRoute('app_product_import');
            }

            try {
                $csv = Reader::createFromPath($file->getPathname(), 'r');
                $csv->setDelimiter(';');
                $csv->setHeaderOffset(0);

                $imported = 0;
                $updated = 0;
                $errors = [];

                foreach ($csv as $index => $row) {
                    try {
                        $code = $row['Code'] ?? '';
                        $product = $code ? $this->productRepository->findOneBy(['code' => $code]) : null;

                        if (!$product) {
                            $product = new Product();
                            $product->setCode($code ?: $this->referenceGenerator->generateProductCode($row['Type'] ?? 'SERVICE'));
                            $imported++;
                        } else {
                            $updated++;
                        }

                        $product->setName($row['Nom'] ?? 'Sans nom');
                        $product->setType($row['Type'] ?? Product::TYPE_SERVICE);
                        $product->setDescription($row['Description'] ?? null);
                        $product->setUnitPrice((string) ($row['Prix unitaire'] ?? '0'));
                        $product->setUnit($row['Unité'] ?? 'unité');

                        // Champs spécifiques par type
                        if ($product->getType() === Product::TYPE_LOGICIEL) {
                            $product->setVersion($row['Version'] ?? null);
                            $product->setLicenseType($row['Type licence'] ?? null);
                            $product->setLicenseDuration(isset($row['Durée licence (mois)']) ? (int) $row['Durée licence (mois)'] : null);
                            $product->setMaxUsers(isset($row['Max utilisateurs']) ? (int) $row['Max utilisateurs'] : null);
                        } elseif ($product->getType() === Product::TYPE_MATERIEL) {
                            $product->setBrand($row['Marque'] ?? null);
                            $product->setModel($row['Modèle'] ?? null);
                            $product->setWarrantyMonths(isset($row['Garantie (mois)']) ? (int) $row['Garantie (mois)'] : null);
                        } elseif ($product->getType() === Product::TYPE_SERVICE) {
                            $product->setDurationHours(isset($row['Durée service (h)']) ? (int) $row['Durée service (h)'] : null);
                        }

                        $this->entityManager->persist($product);
                    } catch (\Exception $e) {
                        $errors[] = "Ligne " . ($index + 2) . ": " . $e->getMessage();
                    }
                }

                $this->entityManager->flush();

                $this->addFlash('success', sprintf('%d produits importés, %d mis à jour.', $imported, $updated));

                if (count($errors) > 0) {
                    foreach (array_slice($errors, 0, 5) as $error) {
                        $this->addFlash('warning', $error);
                    }
                    if (count($errors) > 5) {
                        $this->addFlash('warning', sprintf('... et %d autres erreurs.', count($errors) - 5));
                    }
                }

                return $this->redirectToRoute('app_product_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'import: ' . $e->getMessage());
            }
        }

        return $this->render('product/import.html.twig');
    }

    #[Route('/api/{id}', name: 'app_product_api', methods: ['GET'])]
    public function apiGet(Product $product): Response
    {
        return $this->json([
            'id' => $product->getId(),
            'code' => $product->getCode(),
            'name' => $product->getName(),
            'type' => $product->getType(),
            'typeLabel' => $product->getTypeLabel(),
            'description' => $product->getDescription(),
            'unitPrice' => $product->getUnitPriceFloat(),
            'unit' => $product->getUnit(),
            'characteristics' => $product->getFormattedCharacteristics(),
        ]);
    }
}
