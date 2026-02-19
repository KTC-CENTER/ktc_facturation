<?php

namespace App\Controller;

use App\Repository\InvoiceRepository;
use App\Repository\ProformaRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reports')]
#[IsGranted('ROLE_VIEWER')]
class ReportController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceRepository $invoiceRepository,
        private ProformaRepository $proformaRepository,
    ) {}

    #[Route('', name: 'app_report_index')]
    public function index(): Response
    {
        $conn = $this->entityManager->getConnection();

        // Monthly revenue for last 12 months
        $monthlyRevenue = [];
        try {
            $sql = "SELECT DATE_FORMAT(i.issue_date, '%Y-%m') as month, 
                           SUM(i.total_ttc) as total,
                           COUNT(i.id) as count
                    FROM invoice i 
                    WHERE i.status = 'PAID' 
                      AND i.issue_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY month ORDER BY month ASC";
            $monthlyRevenue = $conn->fetchAllAssociative($sql);
        } catch (\Exception $e) {}

        // Top clients by revenue
        $topClients = [];
        try {
            $sql = "SELECT c.name, SUM(i.total_ttc) as revenue, COUNT(i.id) as invoice_count
                    FROM invoice i JOIN client c ON i.client_id = c.id
                    WHERE i.status = 'PAID'
                    GROUP BY c.id, c.name ORDER BY revenue DESC LIMIT 10";
            $topClients = $conn->fetchAllAssociative($sql);
        } catch (\Exception $e) {}

        // Status breakdown
        $invoicesByStatus = [];
        try {
            $sql = "SELECT status, COUNT(*) as count, SUM(total_ttc) as total FROM invoice GROUP BY status";
            $invoicesByStatus = $conn->fetchAllAssociative($sql);
        } catch (\Exception $e) {}

        $proformasByStatus = [];
        try {
            $sql = "SELECT status, COUNT(*) as count, SUM(total_ttc) as total FROM proforma GROUP BY status";
            $proformasByStatus = $conn->fetchAllAssociative($sql);
        } catch (\Exception $e) {}

        // Totals
        $totalRevenue = 0;
        $totalPending = 0;
        $totalInvoices = 0;
        $totalProformas = 0;
        try {
            $totalRevenue = (float)($conn->fetchOne("SELECT COALESCE(SUM(total_ttc),0) FROM invoice WHERE status = 'PAID'") ?? 0);
            $totalPending = (float)($conn->fetchOne("SELECT COALESCE(SUM(total_ttc),0) FROM invoice WHERE status IN ('SENT','OVERDUE')") ?? 0);
            $totalInvoices = (int)($conn->fetchOne("SELECT COUNT(*) FROM invoice") ?? 0);
            $totalProformas = (int)($conn->fetchOne("SELECT COUNT(*) FROM proforma") ?? 0);
        } catch (\Exception $e) {}

        return $this->render('report/index.html.twig', [
            'monthlyRevenue' => $monthlyRevenue,
            'topClients' => $topClients,
            'invoicesByStatus' => $invoicesByStatus,
            'proformasByStatus' => $proformasByStatus,
            'totalRevenue' => $totalRevenue,
            'totalPending' => $totalPending,
            'totalInvoices' => $totalInvoices,
            'totalProformas' => $totalProformas,
        ]);
    }

    #[Route('/proformas', name: 'app_report_proformas')]
    public function proformas(Request $request, PaginatorInterface $paginator): Response
    {
        $status = $request->query->get('status', '');
        $qb = $this->proformaRepository->createQueryBuilder('p')
            ->leftJoin('p.client', 'c')->addSelect('c')
            ->orderBy('p.issueDate', 'DESC');

        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 20);

        return $this->render('report/proformas.html.twig', [
            'proformas' => $pagination,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/invoices', name: 'app_report_invoices')]
    public function invoices(Request $request, PaginatorInterface $paginator): Response
    {
        $status = $request->query->get('status', '');
        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')->addSelect('c')
            ->orderBy('i.issueDate', 'DESC');

        if ($status) {
            $qb->andWhere('i.status = :status')->setParameter('status', $status);
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 20);

        return $this->render('report/invoices.html.twig', [
            'invoices' => $pagination,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/unpaid', name: 'app_report_unpaid')]
    public function unpaid(Request $request, PaginatorInterface $paginator): Response
    {
        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')->addSelect('c')
            ->where('i.status IN (:statuses)')
            ->setParameter('statuses', ['SENT', 'OVERDUE'])
            ->orderBy('i.dueDate', 'ASC');

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 20);

        return $this->render('report/unpaid.html.twig', [
            'invoices' => $pagination,
        ]);
    }

    #[Route('/revenue', name: 'app_report_revenue')]
    public function revenue(Request $request, PaginatorInterface $paginator): Response
    {
        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')->addSelect('c')
            ->where('i.status = :status')
            ->setParameter('status', 'PAID')
            ->orderBy('i.paidAt', 'DESC');

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 20);

        return $this->render('report/revenue.html.twig', [
            'invoices' => $pagination,
        ]);
    }
}
