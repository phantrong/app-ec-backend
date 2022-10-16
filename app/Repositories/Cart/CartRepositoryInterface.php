<?php

namespace App\Repositories\Cart;

use App\Repositories\RepositoryInterface;

interface CartRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * get cart by key
     *
     * @param  string|int $key (cart key or customer id)
     * @return collections
     */
    public function getCartByKey($key);

    /**
     * create cart
     *
     * @param  string|int $key (cart key or customer id)
     * @return collection
     */
    public function createCart($key);

    /**
     * get list product cart by key
     *
     * @param  string|int $key (cart key or customer id)
     * @param  array|null $cartItemIds
     * @return collection
     */
    public function getProductByKey($key);

    /**
     * get list product cart use create order
     *
     * @param  string|int $key (cart key or customer id)
     * @param  array $cartItemIds
     * @return collection
     */
    public function getProductUseCreateOrder($key, $cartItemIds);
}
