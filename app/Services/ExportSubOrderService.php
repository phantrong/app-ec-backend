<?php

namespace App\Services;

use App\Enums\EnumSubOrder;
use App\Exports\SubOrderExport;
use App\Repositories\SubOrder\SubOrderRepository;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ExportSubOrderService
{
    private $subOrderRepository;

    public function __construct(
        SubOrderRepository $subOrderRepository
    ) {
        $this->subOrderRepository = $subOrderRepository;
    }

    /**
     *
     * @param  Carbon $date
     * @return array
     */
    public function getDataExportSubOrder($fillter, $storeId)
    {
        $dataExport = [];
        $dataColumn = [
            'stt' => '番目',
            'code' => '注文コード',
            'receiver_name' => '顧客名',
            'receiver_phone' => '電話番号',
            'receiver_address' => '住所 ',
            'quantity' => '数量',
            'total_payment' => '合計金格',
            'status' => 'ステータス',
            'ordered_at' => '注文日',
            'products' => '商品名'
        ];

        $orders = $this->subOrderRepository->getDateExportSubOrder($fillter, $storeId);
        foreach ($orders as $key => $order) {
            $nameProducts = [];
            foreach ($order->orderItems as $product) {
                $nameProduct = $product->productClass->product->name . '(';
                foreach ($product->productClass->productTypeConfigs as $item) {
                    $nameProduct .= $item->type_name . " " . $item->name . '/ ';
                }
                $nameProduct .= $product->quantity . ')';
                $nameProducts[] = $nameProduct;
            }
            $data = [
                'stt' => $key + 1,
                'code' => $order->code,
                'receiver_name' => $order->receiver_name,
                'receiver_phone' => $order->receiver_phone,
                'receiver_address' => $order->receiver_address,
                'quantity' => $order->quantity,
                'total_payment' => $order->total_payment,
                'status' => EnumSubOrder::TEXT_STATUS[$order->status - 1],
                'ordered_at' => $order->ordered_at,
                'products' => implode(" - ", $nameProducts)
            ];
            $dataExport[] = $data;
        }
        return [
            "data" => $dataExport,
            "columnName" => $dataColumn,
        ];
    }

    public function exportCsv($fillter, $storeId)
    {
        $dataExport = $this->getDataExportSubOrder($fillter, $storeId);
        $dateString = Carbon::now()->year . sprintf('%02d', Carbon::now()->month) . sprintf('%02d', Carbon::now()->day);
        $file = Excel::download(new SubOrderExport($dataExport), '注文内容' . $dateString . '.xlsx');
        ob_end_clean();
        return $file;
    }
}
