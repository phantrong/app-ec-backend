<?php

namespace App\Repositories\Store;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StoreRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * Get list of products.
     *
     * @param array $request
     * @return LengthAwarePaginator
     */
    public function getList(array $request);

    /**
     * getStore
     *
     * @param  int $storeId
     * @return object
     */
    public function getStore($storeId);

     /**
     * get store site shop
     *
     * @param  int $storeId
     * @return object
     */
    public function getStoreInfo($storeId);

    /**
     * Get total livestream of store.
     *
     * @param int $storeId
     * @param array $bookingStatusArr
     * @return mixed
     */
    public function getTotalLivestreamWithStatus(int $storeId, array $bookingStatusArr);

    /**
     * Get detail bank
     *
     * @param int $storeId
     * @return object|null
     */
    public function getDetailBank($storeId);

    /**
     * Get store by account stripe
     *
     * @param string $accountId
     * @return object|null
     */
    public function findStoreByAccountStripe($accountId);
}
