<?php

namespace App\Repositories\Order;

use App\Repositories\RepositoryInterface;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * @param  array $dataOrder (status,total,total_payment)
     * @return collection|null
     */
    public function createOrder($dataOrder);

    /**
     * updateSuccessOrder
     *
     * @param  int $orderId
     * @return boolean
     */
    public function updateSuccessOrder($orderId);

    /**
     * get detail order
     *
     * @param  int $orderId
     * @return object
     */
    public function getDetailOrder($orderId);
}
