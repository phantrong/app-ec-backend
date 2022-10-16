<?php

namespace App\Repositories\CartItem;

use App\Repositories\RepositoryInterface;

interface CartItemRepositoryInterface extends RepositoryInterface
{
    public function getModel();


    /**
     * @param  int $cartId
     * @param  int $productClassId
     * @param  int $quantity
     * @return int
     */
    public function createCarItem($cartId, $productClassId, $quantity);

    /**
     * @param  int $productClassId
     * @param  string|int $cartDelete (cart key or customer id)
     * @return void
     */
    public function forceDeletedCartItemByProduct($productClassId, $cartDelete);


    /**
     *
     * @param  string|int $cartDelete (cart key or customer id)
     * @param  array $cartItemIds
     * @return boolean
     */
    public function forceDeleteCartItemIds($keyDelete, $cartItemIds);
}
