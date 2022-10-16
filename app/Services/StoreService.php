<?php

namespace App\Services;

use App\Enums\EnumBookingStatus;
use App\Enums\EnumProduct;
use App\Enums\EnumStore;
use App\Enums\EnumSubOrder;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Staff\StaffRepository;
use App\Repositories\Store\StoreRepository;
use App\Repositories\SubOrder\SubOrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class StoreService
{
    private StoreRepository $storeRepository;
    private StaffRepository $staffRepository;
    private SubOrderRepository $subOrderRepository;
    private ProductRepository $productRepository;

    public function __construct(
        StoreRepository $storeRepository,
        StaffRepository $staffRepository,
        SubOrderRepository $subOrderRepository,
        ProductRepository $productRepository
    ) {
        $this->storeRepository = $storeRepository;
        $this->staffRepository = $staffRepository;
        $this->subOrderRepository = $subOrderRepository;
        $this->productRepository = $productRepository;
    }

    public function getStore($storeId)
    {
        return $this->storeRepository->getStore($storeId);
    }

    public function searchStore($request)
    {
        return $this->storeRepository->searchStore($request);
    }

    public function getStoreInfo($storeId)
    {
        $store = $this->storeRepository->getStoreInfo($storeId);
        $productStocking = $this->productRepository->getProductStockingByStore($storeId);
        $store->total_product = $productStocking;
        return $store;
    }

    public function createStore(array $data)
    {
        return $this->storeRepository->create($data);
    }

    public function updateStore($storeId, $data)
    {
        return $this->storeRepository->update($storeId, $data);
    }

    /**
     * Get store list in CMS.
     *
     * @param array $condition
     * @return LengthAwarePaginator
     */
    public function getListStoreCMS(array $condition)
    {
        return $this->storeRepository->getList($condition);
    }

    /**
     * Get store detail in CMS.
     *
     * @param int $id
     * @return object|array
     */
    public function getStoreDetailCMS(int $id)
    {
        return $this->storeRepository->getStore($id);
    }

    /**
     * Get store detail in CMS.
     *
     * @param int $id
     * @param array $condition
     * @return bool|mixed
     */
    public function settingCommission(int $id, array $condition)
    {
        return $this->storeRepository->update($id, [
            'commission' => $condition['commission'],
            'date_applicable_commission' => now()->format('Y-m-d H:i:s')
        ]);
    }

    public function getListInstagram($request)
    {
        return $this->storeRepository->getListInstagram($request);
    }

    public function getDetailBank($storeId)
    {
        return $this->storeRepository->getDetailBank($storeId);
    }

    public function findStoreByAccountStripe($accountId)
    {
        return $this->storeRepository->findStoreByAccountStripe($accountId);
    }
}
