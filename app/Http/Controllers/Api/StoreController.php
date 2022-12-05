<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnumSubOrder;
use App\Http\Requests\RevenueOrderRequest;
use App\Http\Requests\RevenueRequest;
use App\Http\Requests\SettingCommissionRequest;
use App\Services\ManagerRevenueService;
use App\Services\StoreService;
use App\Services\SubOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends BaseController
{
    private StoreService $storeService;
    private SubOrderService $subOrderService;
    private ManagerRevenueService $revenueService;

    public function __construct(
        StoreService $storeService,
        SubOrderService $subOrderService,
        ManagerRevenueService $revenueService
    ) {
        $this->storeService = $storeService;
        $this->subOrderService = $subOrderService;
        $this->revenueService = $revenueService;
    }

    public function searchStore(Request $request): JsonResponse
    {
        try {
            $stores = $this->storeService->searchStore($request->all());
            return $this->sendResponse($stores);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    // get info store in product detail
    public function getStoreInfo($storeId): JsonResponse
    {
        try {
            $stores = $this->storeService->getStoreInfo($storeId);
            return $this->sendResponse($stores);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getRevenueOrderByStore(RevenueOrderRequest $request): JsonResponse
    {
        try {
            $storeId = $request->user()->store_id;
            $type = $request->type ?? EnumSubOrder::UNIT_DAY;
            $isPostStartDate = $request->start_date;
            $isPostEndDate = $request->end_date;
            $dataRevenue = $this->revenueService->handleDateRevenue(
                $request->start_date,
                $request->end_date,
                $type,
                $isPostStartDate,
                $isPostEndDate
            );
            $startDate = $dataRevenue['start_date'];
            $endDate = $dataRevenue['end_date'];
            $revenue = $this->revenueService->getRevenueOrderByStore($startDate, $endDate, $type, $storeId);
            return $this->sendResponse($revenue);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function exportRevenueOfStoreByOrder(Request $request)
    {
        try {
            $storeId = $request->user()->store_id;
            $type = $request->type ?? EnumSubOrder::UNIT_DAY;
            $isPostStartDate = $request->start_date;
            $isPostEndDate = $request->end_date;
            $dataRevenue = $this->revenueService->handleDateRevenue(
                $request->start_date,
                $request->end_date,
                $type,
                $isPostStartDate,
                $isPostEndDate
            );
            $startDate = $dataRevenue['start_date'];
            $endDate = $dataRevenue['end_date'];
            return $this->revenueService->exportRevenueOrderByStore($startDate, $endDate, $type, $storeId);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getRevenueOfStoreByProduct(Request $request): JsonResponse
    {
        try {
            $storeId = $request->user()->store_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $products = $this->revenueService->getRevenueByProduct($startDate, $endDate, $storeId);
            return $this->sendResponse($products);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function exportRevenueOfStoreByProduct(Request $request)
    {
        try {
            $storeId = $request->user()->store_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            return $this->revenueService->exportRevenueProduct($startDate, $endDate, $storeId);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function statisticRevenueOfStoreByAge(Request $request): JsonResponse
    {
        try {
            $storeId = $request->user()->store_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $data = $this->revenueService->statisticOrderByAge($startDate, $endDate, $storeId);
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function exportRevenueOfStoreByAge(Request $request)
    {
        try {
            $storeId = $request->user()->store_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            return $this->revenueService->exportRevenueOfStoreByAge($startDate, $endDate, $storeId);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get store list in CMS.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getListStoreCMS(Request $request): JsonResponse
    {
        try {
            $stores = $this->storeService->getListStoreCMS($request->all());
            return $this->sendResponse($stores);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get store detail in CMS.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getStoreDetailCMS(int $id)
    {
        try {
            $store = $this->storeService->getStoreDetailCMS($id);
            if (isset($store['errorCode'])) {
                return $this->sendResponse($store['errorCode'], $store['status']);
            }
            return $this->sendResponse($store);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Setting commission in store.
     *
     * @param int $id
     * @param SettingCommissionRequest $request
     * @return JsonResponse
     */
    public function settingCommission(int $id, SettingCommissionRequest $request)
    {
        try {
            $store = $this->storeService->settingCommission($id, $request->all());
            return $this->sendResponse($store);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getRevenue(RevenueRequest $request)
    {
        try {
            $store = Auth::user()->store;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $data = $this->subOrderService->getRevenue($startDate, $endDate, $store->id) ?: [];
            $data['commission'] = @$store->commission ?? 0;
            $data['date_applicable_commission'] = $store->date_applicable_commission;
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getDetailBank($storeId)
    {
        try {
            $bank = $this->storeService->getDetailBank($storeId);
            return $this->sendResponse($bank);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
