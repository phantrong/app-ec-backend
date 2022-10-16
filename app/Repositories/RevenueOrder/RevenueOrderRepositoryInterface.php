<?php

namespace App\Repositories\RevenueOrder;

use App\Repositories\RepositoryInterface;

interface RevenueOrderRepositoryInterface extends RepositoryInterface
{
    public function getModel();


    /**
     * Get list of active staff.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $storeId
     * @return object
     */
    public function getRevenueByDate($startDate, $endDate, $type, $storeId = null);

    /**
     * get total revenue store and revenue actual
     *
     * @param  string  $startDate
     * @param string $endDate
     * @param  int|null  $storeId
     * @return object
     */
    public function getRevenueTotal($startDate, $endDate, $storeId = null);
}
