<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\ProformaRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProductRepository;
use App\Entity\Proforma;
use App\Entity\Invoice;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_VIEWER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private ClientRepository $clientRepository,
        private ProformaRepository $proformaRepository,
        private InvoiceRepository $invoiceRepository,
        private ProductRepository $productRepository
    ) {}

    #[Route('', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Statistiques générales
        $stats = [
            'clients' => $this->clientRepository->count(['isArchived' => false]),
            'products' => $this->productRepository->count(['isActive' => true]),
            'proformas' => $this->getProformaStats(),
            'invoices' => $this->getInvoiceStats(),
        ];

        // Proformas récentes
        $recentProformas = $this->proformaRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        // Factures récentes
        $recentInvoices = $this->invoiceRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        // Proformas expirantes (dans les 7 prochains jours)
        $expiringProformas = $this->proformaRepository->findExpiringSoon(7);

        // Factures impayées
        $unpaidInvoices = $this->invoiceRepository->findBy(
            ['status' => Invoice::STATUS_SENT],
            ['dueDate' => 'ASC'],
            5
        );

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'recentProformas' => $recentProformas,
            'recentInvoices' => $recentInvoices,
            'expiringProformas' => $expiringProformas,
            'unpaidInvoices' => $unpaidInvoices,
        ]);
    }

    #[Route('/stats/revenue', name: 'app_dashboard_revenue_stats', methods: ['GET'])]
    public function revenueStats(): JsonResponse
    {
        $currentYear = (int) date('Y');
        $monthlyRevenue = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = new \DateTime("$currentYear-$month-01");
            $endDate = (clone $startDate)->modify('last day of this month');
            
            $revenue = $this->invoiceRepository->getTotalRevenueByPeriod($startDate, $endDate);
            $monthlyRevenue[] = [
                'month' => $startDate->format('M'),
                'revenue' => $revenue,
            ];
        }

        return $this->json([
            'labels' => array_column($monthlyRevenue, 'month'),
            'data' => array_column($monthlyRevenue, 'revenue'),
        ]);
    }

    #[Route('/stats/status', name: 'app_dashboard_status_stats', methods: ['GET'])]
    public function statusStats(): JsonResponse
    {
        $proformaStats = [];
        foreach (Proforma::STATUSES as $status => $label) {
            $count = $this->proformaRepository->count(['status' => $status]);
            if ($count > 0) {
                $proformaStats[] = [
                    'status' => $label,
                    'count' => $count,
                    'color' => Proforma::STATUS_COLORS[$status] ?? '#6B7280',
                ];
            }
        }

        return $this->json($proformaStats);
    }

    #[Route('/stats/type', name: 'app_dashboard_type_stats', methods: ['GET'])]
    public function typeStats(): JsonResponse
    {
        $typeStats = $this->invoiceRepository->getRevenueByProductType();

        return $this->json($typeStats);
    }

    private function getProformaStats(): array
    {
        return [
            'total' => $this->proformaRepository->count([]),
            'draft' => $this->proformaRepository->count(['status' => Proforma::STATUS_DRAFT]),
            'sent' => $this->proformaRepository->count(['status' => Proforma::STATUS_SENT]),
            'accepted' => $this->proformaRepository->count(['status' => Proforma::STATUS_ACCEPTED]),
            'invoiced' => $this->proformaRepository->count(['status' => Proforma::STATUS_INVOICED]),
            'expired' => $this->proformaRepository->count(['status' => Proforma::STATUS_EXPIRED]),
            'refused' => $this->proformaRepository->count(['status' => Proforma::STATUS_REFUSED]),
        ];
    }

    private function getInvoiceStats(): array
    {
        $totalPaid = $this->invoiceRepository->getTotalByStatus(Invoice::STATUS_PAID);
        $totalPending = $this->invoiceRepository->getTotalByStatus(Invoice::STATUS_SENT);
        
        return [
            'total' => $this->invoiceRepository->count([]),
            'draft' => $this->invoiceRepository->count(['status' => Invoice::STATUS_DRAFT]),
            'sent' => $this->invoiceRepository->count(['status' => Invoice::STATUS_SENT]),
            'paid' => $this->invoiceRepository->count(['status' => Invoice::STATUS_PAID]),
            'partial' => $this->invoiceRepository->count(['status' => Invoice::STATUS_PARTIAL]),
            'overdue' => $this->invoiceRepository->count(['status' => Invoice::STATUS_OVERDUE]),
            'cancelled' => $this->invoiceRepository->count(['status' => Invoice::STATUS_CANCELLED]),
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
        ];
    }
}
