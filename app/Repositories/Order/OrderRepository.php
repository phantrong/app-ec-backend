<?php

namespace App\Repositories\Order;

use App\Enums\EnumOrder;
use App\Enums\EnumSubOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SubOrder;
use App\Repositories\BaseRepository;
use App\Repositories\SubOrder\SubOrderRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function getModel()
    {
        return Order::class;
    }

    public function createOrder($dataOrder)
    {
        $now = Carbon::now();
        DB::beginTransaction();
        try {
            $order =  $this->model->create([
                'status' => $dataOrder['status'],
                'total' => $dataOrder['total'],
                'order_code' => 'order_code',
                'total_payment' => $dataOrder['total_payment'],
                'ordered_at' => $now,
                'customer_id' => $dataOrder['customer_id']
            ]);
            $order->order_code = $now->format('Ymd') . '_' . sprintf('%03d', $order->id);
            $order->save();
            DB::commit();
            return $order;
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e);
            return;
        }
    }

    public function updateSuccessOrder($orderId)
    {
        $order = $this->model
            ->where('id', $orderId)
            ->withCount([
                'subOrders as total_success' => function ($query) {
                    return $query->where('status', EnumSubOrder::STATUS_SHIPPED);
                },
                'subOrders as total_order'
            ])
            ->first();
        if ($order && $order->total_success == $order->total_order) {
            return $order->update([
                'status' => EnumOrder::STATUS_DONE
            ]);
        }
        return;
    }

    public function getDetailOrder($orderId)
    {
        $tblOrder = Order::getTableName();
        $tblCustomer = Customer::getTableName();
        $tblSubOrder = SubOrder::getTableName();
        $tblOrderItem = OrderItem::getTableName();
        return $this->model
            ->select(
                "$tblOrder.id",
                'order_code',
                "$tblOrder.total_payment",
                'ordered_at',
                "$tblCustomer.name",
                "$tblCustomer.surname",
                DB::raw("SUM($tblOrderItem.quantity) as total_product")
            )
            ->leftjoin($tblCustomer, "$tblCustomer.id", '=', "$tblOrder.customer_id")
            ->join($tblSubOrder, "$tblSubOrder.order_id", '=', "$tblOrder.id")
            ->join($tblOrderItem, "$tblSubOrder.id", '=', "$tblOrderItem.sub_order_id")
            ->where("$tblOrder.id", $orderId)
            ->groupBy("$tblOrder.id")
            ->with([
                'shipping:id,order_id,address_01,address_02,address_03,address_04,receiver_name,receiver_name,email',
                'subOrders:order_id,id,store_id',
                'subOrders.store:id,name',
                'subOrders.orderItems:sub_order_id,product_class_id,quantity',
                'subOrders.orderItems.productClass:id,product_id',
                'subOrders.orderItems.productClass.product:id,name',
                'subOrders.orderItems.productClass.product.productMediasImage',
                'subOrders.orderItems.productClass.getProductTypeDeleted:type_name'
            ])
            ->first();
    }
}
