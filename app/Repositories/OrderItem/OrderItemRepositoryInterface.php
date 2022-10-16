<?php

namespace App\Repositories\OrderItem;

use App\Repositories\RepositoryInterface;

interface OrderItemRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     *  get order item with order new (not paid or su)
     *
     * @param  int $orderId
     * @return collections
     */
    public function getOrderItemByOrderNew($orderId);

    /**
     *
     * @param  int $orderId
     * @return boolean
     */
    public function setOrderItemNullCartItem($orderId);
}
