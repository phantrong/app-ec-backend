<?php

namespace App\Repositories\RevenueAge;

use App\Repositories\RepositoryInterface;

interface RevenueAgeRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * Get revenue by filter
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $storeId
     * @return object
     */
    public function getRevenueByDate($startDate, $endDate, $storeId = null);
}
