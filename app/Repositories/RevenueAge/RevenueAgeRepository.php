<?php

namespace App\Repositories\RevenueAge;

use App\Models\RevenueAge;
use App\Repositories\BaseRepository;

class RevenueAgeRepository extends BaseRepository implements RevenueAgeRepositoryInterface
{

    public function getModel()
    {
        return RevenueAge::class;
    }

    public function getRevenueByDate($startDate, $endDate, $storeId = null)
    {
        return $this->model
            ->selectRaw(
                "age,
                SUM(revenue) as revenue,
                SUM(total_order) as total_order,
                SUM(revenue) / SUM(total_order) as average"
            )
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('date_revenue', '>=', $startDate);
            })->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('date_revenue', '<=', $endDate);
            })->when($storeId, function ($query) use ($storeId) {
                return $query->where('store_id', $storeId);
            })
            ->groupBy('age')
            ->orderBy('age')
            ->get();
    }
}
