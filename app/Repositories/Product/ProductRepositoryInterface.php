<?php

namespace App\Repositories\Product;

use App\Repositories\RepositoryInterface;

interface ProductRepositoryInterface extends RepositoryInterface
{

    /**
     * Get all
     * search product by name
     *
     * @param  array  $request
     * @param  mixed  $customerId
     * @param  bool  $isFavorite
     * @return mixed
     */
    public function searchProduct(array $request, mixed $customerId, $isFavorite): mixed;

    /**
     * Get total product of store.
     *
     * @param  int  $storeId
     * @param  array  $productStatusArr
     * @return int
     */
    public function getTotalProductWithStatus(int $storeId, array $productStatusArr);

    /**
     * Get total product by store.
     *
     * @param  int  $storeId
     * @param  array  $request
     * @param  bool  $isCMS
     * @param  bool  $isPagiante
     * @return object
     */
    public function getAllProductByStore($request, $storeId, $isCMS = false, $isPagiante = true);


    /**
     * Get revenue daily.
     *
     * @param  string|null  $date
     * @param  int|null  $storeId
     * @param  bool  $getName
     * @return object
     */
    public function getRevenueProductDaily($date = null, $storeId = null, $getName = false);

    /**
     * Get top product revenue .
     *
     * @param  int|null  $storeId
     * @param  string  $startDate
     * @param  string  $endDate
     * @return object
     */
    public function getProductRevenueBest($startDate, $endDate, $storeId = null);

    /**
     * Check brand used .
     *
     * @param  int $brandId
     * @return bool
     */
    public function checkBrandUsed($brandId);

    /**
     * Count product stocking of store .
     *
     * @param  int $storeId
     * @return int
     */
    public function getProductStockingByStore($storeId);
}
