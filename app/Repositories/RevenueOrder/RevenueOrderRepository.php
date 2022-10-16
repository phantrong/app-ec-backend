<?php

namespace App\Repositories\RevenueOrder;

use App\Enums\EnumSubOrder;
use App\Models\RevenueOrder;
use App\Repositories\BaseRepository;

class RevenueOrderRepository extends BaseRepository implements RevenueOrderRepositoryInterface
{

    public function getModel()
    {
        return RevenueOrder::class;
    }

    public function getRevenueByDate($startDate, $endDate, $type, $storeId = null)
    {
        $typeDay = EnumSubOrder::UNIT_DAY;
        $typeMonth = EnumSubOrder::UNIT_MONTH;
        $typeYear = EnumSubOrder::UNIT_YEAR;
        return $this->model
            ->selectRaw(
                "CASE WHEN $type = $typeDay THEN revenue_date
                WHEN $type = $typeMonth THEN DATE_FORMAT(revenue_date, '%Y-%m')
                WHEN $type = $typeYear THEN YEAR(revenue_date) END as date,
                SUM(total_order) as number_order,
                SUM(customer_male) as customer_male,
                SUM(customer_female) as customer_female,
                SUM(customer_unknown) as customer_unknown,
                SUM(customer_not_login) as customer_not_login,
                SUM(revenue) as revenue,
                SUM(revenue_actual) as revenue_actual,
                SUM(revenue)/SUM(total_order) as average",
            )
            ->when($storeId, function ($query) use ($storeId) {
                return $query->where('store_id', $storeId);
            })
            ->when($startDate, function ($query) use ($startDate) {
                return $query->where('revenue_date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->where('revenue_date', '<=', $endDate);
            })
            ->groupBy('date')
            ->get();
    }

    public function getRevenueTotal($startDate, $endDate, $storeId = null)
    {
        return $this->model
            ->selectRaw("
                SUM(revenue) as total_revenue,
                SUM(revenue_actual) as revenue_actual
            ")
            ->when($storeId, function ($query) use ($storeId) {
                return $query->where('store_id', $storeId);
            })
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('revenue_date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('revenue_date', '<=', $endDate);
            })
            ->groupBy("store_id")
            ->first();
    }
}
