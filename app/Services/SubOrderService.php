<?php

namespace App\Services;

use App\Enums\EnumSubOrder;
use App\Repositories\RevenueOrder\RevenueOrderRepository;
use App\Repositories\SubOrder\SubOrderRepository;

class SubOrderService
{
    private SubOrderRepository $subOrderRepository;
    private RevenueOrderRepository $revenueOrderRepository;

    public function __construct(
        SubOrderRepository $subOrderRepository,
        RevenueOrderRepository $revenueOrderRepository
    ) {
        $this->subOrderRepository = $subOrderRepository;
        $this->revenueOrderRepository = $revenueOrderRepository;
    }

    public function find($id)
    {
        return $this->subOrderRepository->find($id);
    }

    public function getListSubOrderOfStore($fillter, $storeId)
    {
        return $this->subOrderRepository->getListSubOrderOfStore($fillter, $storeId);
    }

    public function countSubOrderByStatusOfStore($fillter, $storeId)
    {
        $result = $this->subOrderRepository->countSubOrderByStatusOfStore($fillter, $storeId);
        $arrayStatus = [];
        $arrayStatusHad = [];
        foreach ($result as $item) {
            $status['status'] = $item->status;
            $status['count'] = $item->count;
            $arrayStatus[] = $status;
            $arrayStatusHad[] = $item->status;
        }
        foreach (EnumSubOrder::STATUS as $constStatus) {
            if (!in_array($constStatus, $arrayStatusHad)) {
                $status['status'] = $constStatus;
                $status['count'] = 0;
                $arrayStatus[] = $status;
            }
        }
        $status = array_column($arrayStatus, 'status');
        array_multisort($status, SORT_ASC, $arrayStatus);
        return $arrayStatus;
    }

    public function getItemsOfSubOrder($subOrderId)
    {
        return $this->subOrderRepository->getItemsOfSubOrder($subOrderId);
    }

    public function updateSubOrder($subOrderId, $attributes)
    {
        return $this->subOrderRepository->update($subOrderId, $attributes);
    }

    public function getListOrderByCustomer($request, $customerId)
    {
        $listOrder = $this->subOrderRepository->getListOrderByCustomer($request, $customerId);
        $status = $this->countOrderByStatus($request, $customerId);
        return [
            'listOrder' => $listOrder,
            'status' => $status
        ];
    }

    public function countOrderByStatus($request, $customerId): array
    {
        $listOrder = $this->subOrderRepository->getAllOrderByCustomer($request, $customerId);
        $status = [];
        foreach (EnumSubOrder::STATUS as $constStatus) {
            $status[$constStatus] = [
                'status' => $constStatus,
                'total' => 0
            ];
        }

        foreach ($listOrder as $order) {
            $status[$order->status]['total']++;
        }
        return array_values($status);
    }

    public function getDetailOrder($orderId)
    {
        return $this->subOrderRepository->getDetailSubOrder($orderId);
    }

    public function getDetailOrderSiteUser($subOrderId)
    {
        $order = $this->subOrderRepository->getDetailOrderSiteUser($subOrderId);
        if ($order) return $order->toArray();
        return $order;
    }

    public function confirmOrder(int $orderId): bool
    {
        return $this->subOrderRepository->confirmOrder($orderId);
    }

    public function getRevenue($startDate, $endDate, $storeId = null)
    {

        $now = now()->format('Y-m-d');
        $revenueToday = $this->subOrderRepository->statisticRevenueOrderDaily($now, $storeId);
        $revenue = $this->revenueOrderRepository->getRevenueTotal($startDate, $endDate, $storeId);
        if (!$endDate || $endDate >= $now) {
            return [
                'total_revenue' => ($revenueToday->revenue ?? 0) + ($revenue->total_revenue ?? 0),
                'revenue_actual' => ($revenueToday->revenue_actual ?? 0) + ($revenue->revenue_actual ?? 0)
            ];
        }
        return $revenue;
    }

    public function getInfoOrderExportPdf($subOrderId)
    {
        return $this->subOrderRepository->getInfoOrderExportPdf($subOrderId);
    }
}
