<?php

namespace App\Services;

use App\Enums\EnumCustomer;
use App\Enums\EnumSubOrder;
use App\Exports\ExportRevenue;
use App\Repositories\Product\ProductRepository;
use App\Repositories\RevenueProduct\RevenueProductRepository;
use App\Repositories\RevenueOrder\RevenueOrderRepository;
use App\Repositories\RevenueAge\RevenueAgeRepository;
use App\Repositories\SubOrder\SubOrderRepository;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;

class ManagerRevenueService
{
    const LIMIT_PRODUCT_BEST_SALE = 20;

    private SubOrderRepository $subOrderRepository;
    private ProductRepository $productRepository;
    private RevenueProductRepository $revenueProductRepository;
    private RevenueOrderRepository $revenueOrderRepository;
    private RevenueAgeRepository $revenueAgeRepository;

    public function __construct(
        SubOrderRepository $subOrderRepository,
        ProductRepository $productRepository,
        RevenueProductRepository $revenueProductRepository,
        RevenueOrderRepository $revenueOrderRepository,
        RevenueAgeRepository $revenueAgeRepository
    ) {
        $this->subOrderRepository = $subOrderRepository;
        $this->productRepository = $productRepository;
        $this->revenueProductRepository = $revenueProductRepository;
        $this->revenueOrderRepository = $revenueOrderRepository;
        $this->revenueAgeRepository = $revenueAgeRepository;
    }

    public function getRevenueOrderByStore($startDate, $endDate, $type, $storeId = null)
    {
        $revenues = $this->revenueOrderRepository->getRevenueByDate($startDate, $endDate, $type, $storeId);
        $formatDate = $this->handleDateFormatRevenue($type);
        $endDate = Carbon::parse($endDate)->format($formatDate);
        $today = now()->format('Y-m-d');
        $revenueToday = null;
        if (!$endDate
            || $endDate >= now()->format($formatDate)) {
            $revenueToday = $this->subOrderRepository->statisticRevenueOrderDaily($today, $storeId);
        }
        $data = [];
        $startDate = Carbon::parse($startDate);
        $index = 0;
        while ($endDate >= $startDate->format($formatDate)) {
            $dateCheck = $startDate->format($formatDate);
            if (isset($revenues[$index]) && $dateCheck == $revenues[$index]->date) {
                $revenue = $revenues[$index]->toArray();
                $index++;
            } else {
                $revenue = [
                    'date' => $dateCheck,
                    'revenue' => 0,
                    'revenue_actual' => 0,
                    'average' => 0,
                    'number_order' => 0,
                    'customer_male' => 0,
                    'customer_female' => 0,
                    'customer_unknown' => 0,
                    'customer_not_login' => 0
                ];
            }
            if ($revenueToday && now()->format($formatDate) == $startDate->format($formatDate)) {
                $revenue['revenue'] += $revenueToday->revenue;
                $revenue['revenue_actual'] += $revenueToday->revenue_actual;
                $revenue['number_order'] += $revenueToday->total_order;
                $revenue['customer_male'] += $revenueToday->customer_male;
                $revenue['customer_female'] += $revenueToday->customer_female;
                $revenue['customer_unknown'] += $revenueToday->customer_unknown;
                $revenue['customer_not_login'] += $revenueToday->customer_not_login;
                $revenue['average'] = $revenue['revenue'] / $revenue['number_order'];
            }
            $data[] = $revenue;
            $startDate = $this->addDateRevenue($startDate, $type);
        }
        return $data;
    }

    public function addDateRevenue($date, $type)
    {
        switch ($type) {
            case EnumSubOrder::UNIT_DAY:
                $date = $date->addDay(1);
                break;
            case EnumSubOrder::UNIT_YEAR:
                $date = $date->addYear(1);
                break;
            default:
                $date = $date->addMonth(1);
                break;
        }
        return $date;
    }

    public function handleDateFormatRevenue($type): string
    {
        switch ($type) {
            case EnumSubOrder::UNIT_MONTH:
                $format = 'Y-m';
                break;
            case EnumSubOrder::UNIT_YEAR:
                $format = 'Y';
                break;
            default:
                $format = 'Y-m-d';
                break;
        }
        return $format;
    }

    public function handleDateRevenue($startDate, $endDate, $type, $isPostStartDate, $issPostEndDate)
    {
        $format = $this->handleDateFormatRevenue($type);
        $startDate = $startDate ? Carbon::createFromFormat($format, $startDate) : now();
        $endDate = $endDate ? Carbon::createFromFormat($format, $endDate) : now();
        switch ($type) {
            case EnumSubOrder::UNIT_DAY:
                $startDate = $isPostStartDate ?
                    $startDate->format('Y-m-d') :
                    $startDate->startOfMonth()->format('Y-m-d');
                $endDate = $issPostEndDate ?
                    $endDate->format('Y-m-d') :
                    $endDate->endOfMonth()->format('Y-m-d');
                break;
            case EnumSubOrder::UNIT_MONTH:
                $startDate = $startDate->startOfMonth()->format('Y-m-d');
                $endDate = $endDate->endOfMonth()->format('Y-m-d');
                break;
            default:
                $startDate = $startDate->startOfYear()->format('Y-m-d');
                $endDate = $endDate->endOfYear()->format('Y-m-d');
                break;
        }
        return [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    public function exportRevenueOrderByStore($startDate, $endDate, $type, $storeId = null)
    {
        $columns = [
            'date' => trans('heading.revenue_order.date'),
            'number_order' => trans('heading.revenue_order.number_order'),
            'revenue' => trans('heading.revenue_order.revenue'),
            'revenue_actual' => trans('heading.revenue_order.revenue_actual'),
            'average' => trans('heading.revenue_order.average'),
            'customer_male' => trans('heading.revenue_order.customer_male'),
            'customer_female' => trans('heading.revenue_order.customer_female'),
            'customer_unknown' => trans('heading.revenue_order.customer_unknown'),
            'customer_not_login' => trans('heading.revenue_order.customer_not_login'),
        ];
        $sheetName = trans('heading.revenue_order.file_name').now()->format('Y_m_d').'.xlsx';
        $dataExport = $this->getRevenueOrderByStore($startDate, $endDate, $type, $storeId);
        $dataExport = $this->getDataExportRevenueOrder($dataExport);
        $file = Excel::download(new ExportRevenue($dataExport, $columns), $sheetName);
        ob_end_clean();
        return $file;
    }

    public function getDataExportRevenueOrder($data): array
    {
        foreach ($data as $revenue) {
            $dataExport[] = [
                'date' => ''.$revenue['date'],
                'number_order' => $revenue['number_order'] ?: '0',
                'revenue' => $revenue['revenue'] ?: '0',
                'revenue_actual' => $revenue['revenue_actual'] ?: '0',
                'average' => $revenue['average'] ?: '0',
                'customer_male' => $revenue['customer_male'] ?: '0',
                'customer_female' => $revenue['customer_female'] ?: '0',
                'customer_unknown' => $revenue['customer_unknown'] ?: '0',
                'customer_not_login' => $revenue['customer_not_login'] ?: '0',
            ];
        }
        return $dataExport;
    }

    public function getRevenueByProduct($startDate, $endDate, $storeId = null)
    {
        $now = now()->format('Y-m-d');
        $revenues = $this->productRepository->getProductRevenueBest($startDate, $endDate, $storeId)->toArray();
        $productId = array_column($revenues, 'product_id');
        if (!$endDate
            || $endDate >= $now) {
            $revenueToday = $this->productRepository->getRevenueProductDaily($now, $storeId, true)->toArray();
            if ($revenues) {
                foreach ($revenues as $index => $revenue) {
                    foreach ($revenueToday as $position => $item) {
                        if ($item['product_id'] == $revenue['product_id']) {
                            $revenue['revenue'] += $item['revenue'];
                            $revenue['total_order'] += $item['total_order'];
                            $revenue['total_product'] += $item['total_product'];
                            $revenues[$index] = $revenue;
                            unset($revenueToday[$position]);
                        } elseif (!in_array($item['product_id'], $productId)) {
                            $revenues[] = Arr::only($item, [
                                'name',
                                'product_id',
                                'revenue',
                                'total_order',
                                'total_product'
                            ]);
                            unset($revenueToday[$index]);
                        }
                    }
                }
            } else {
                $revenues = $revenueToday;
            }
        }
        $products = $this->sortProductRevenue($revenues);
        return array_slice($products, 0, self::LIMIT_PRODUCT_BEST_SALE);
    }


    public function sortProductRevenue($products)
    {
        $numberProduct = count($products);
        for ($i = 0; $i < $numberProduct - 1; $i++) {
            for ($j = $i + 1; $j < $numberProduct; $j++) {
                if ($products[$i]['revenue'] < $products[$j]['revenue']) {
                    $tmp = $products[$j];
                    $products[$j] = $products[$i];
                    $products[$i] = $tmp;
                }
            }
        }
        return $products;
    }

    public function exportRevenueProduct($startDate, $endDate, $storeId = null)
    {
        $dataExport = $this->getRevenueByProduct($startDate, $endDate, $storeId);
        $dataExport = $this->getDataExportRevenueProduct($dataExport);
        $columns = [
            'index' => trans('heading.revenue_product.index'),
            'name' => trans('heading.revenue_product.name'),
            'total_order' => trans('heading.revenue_product.total_order'),
            'total_product' => trans('heading.revenue_product.total_product'),
            'revenue' => trans('heading.revenue_product.revenue'),
        ];
        $sheetName = trans('heading.revenue_product.file_name').now()->format('Y_m_d').'.xlsx';
        $file = Excel::download(new ExportRevenue($dataExport, $columns), $sheetName);
        ob_end_clean();
        return $file;
    }

    public function getDataExportRevenueProduct($data): array
    {
        $dataExport = [];
        foreach ($data as $index => $product) {
            $dataExport[] = [
                'index' => ++$index,
                'name' => $product['name'],
                'total_order' => $product['total_order'] ?: '0',
                'total_product' => $product['total_product'] ?: '0',
                'revenue' => $product['revenue'] ?: '0'
            ];
        }
        return $dataExport;
    }

    public function statisticOrderByAge($startDate, $endDate, $storeId = null)
    {
        $orders = $this->revenueAgeRepository->getRevenueByDate($startDate, $endDate, $storeId)->toArray();
        $now = now()->format('Y-m-d');
        $orderToday = $this->subOrderRepository->statisticRevenueAgeDaily($now, $storeId)->toArray();
        $data = [];
        for ($i = 0; $i < EnumCustomer::NUMBER_AGE_MILESTONE; $i++) {
            $temp = [
                'revenue' => 0,
                'total_order' => 0,
            ];
            foreach ($orders as $key => $order) {
                if ($order['age'] > $i) {
                    break;
                }
                if ($order['age'] == $i) {
                    $temp['revenue'] += $order['revenue'];
                    $temp['total_order'] += $order['total_order'];
                    unset($orders[$key]);
                }
            }

            foreach ($orderToday as $key => $order) {
                if ($order['age'] > $i) {
                    break;
                }
                if ($order['age'] == $i) {
                    $temp['revenue'] += $order['revenue'];
                    $temp['total_order'] += $order['total_order'];
                    unset($orderToday[$key]);
                }
            }
            $temp['average'] = $temp['revenue'] ? $temp['revenue'] / $temp['total_order'] : 0;
            $data[] = $temp;
        }
        return $data;
    }

    public function exportRevenueOfStoreByAge($startDate, $endDate, $storeId = null)
    {
        $dataExport = $this->statisticOrderByAge($startDate, $endDate, $storeId);
        $dataExport = $this->getDataExportRevenueByAge($dataExport);
        $columns = [
            'age' => trans('heading.revenue_age.age'),
            'total_order' => trans('heading.revenue_age.total_order'),
            'revenue' => trans('heading.revenue_age.revenue'),
            'average' => trans('heading.revenue_age.average')
        ];
        $sheetName = trans('heading.revenue_age.file_name').now()->format('Y_m_d').'.xlsx';
        $file = Excel::download(new ExportRevenue($dataExport, $columns), $sheetName);
        ob_end_clean();
        return $file;
    }

    public function getDataExportRevenueByAge($data): array
    {
        $dataExport = [];
        foreach ($data as $index => $item) {
            $age = $index * 10 .'代';
            if ($index == EnumCustomer::NUMBER_AGE_MILESTONE - 1) {
                $age = 'その他';
            }
            $dataExport[] = [
                'age' => $age,
                'total_order' => $item['total_order'] ?: '0',
                'revenue' => $item['revenue'] ?: '0',
                'average' => $item['total_order'] ? ROUND($item['revenue'] / $item['total_order'], 2) : '0'
            ];
        }
        return $dataExport;
    }
}
