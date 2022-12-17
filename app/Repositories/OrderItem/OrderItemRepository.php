<?php

namespace App\Repositories\OrderItem;

use App\Enums\EnumOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SubOrder;
use App\Repositories\BaseRepository;

class OrderItemRepository extends BaseRepository implements OrderItemRepositoryInterface
{
    public function getModel()
    {
        return OrderItem::class;
    }

    /**
     * get order item with order new (not paid or su)
     *
     */
    public function getOrderItemByOrderNew($orderId)
    {
        $tblSubOrder = SubOrder::getTableName();
        $tblOrderItem = OrderItem::getTableName();
        $tblOrder = Order::getTableName();

        return $this->model->rightJoin("$tblSubOrder", "$tblSubOrder.id", '=', "$tblOrderItem.sub_order_id")
            ->leftJoin("$tblOrder", "$tblOrder.id", '=', "$tblSubOrder.order_id")
            ->whereNull("$tblSubOrder.deleted_at")
            ->where("$tblSubOrder.order_id", $orderId)
            ->get();
    }

    public function setOrderItemNullCartItem($orderId)
    {
        $tblSubOrder = SubOrder::getTableName();
        $tblOrderItem = OrderItem::getTableName();

        return $this->model->rightJoin("$tblSubOrder", "$tblSubOrder.id", '=', "$tblOrderItem.sub_order_id")
            ->where("$tblSubOrder.order_id", $orderId)
            ->update(['cart_item_id' => null]);
    }

    public function getOrderByProduct($productId)
    {
        return $this->model->where('product_id', $productId)->first();
    }
}
