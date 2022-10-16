<?php

namespace App\Repositories\ProductClass;

use App\Repositories\RepositoryInterface;

interface ProductClassRepositoryInterface extends RepositoryInterface
{

    /**
     * @param  string $key (cart key or customer id)
     * @param  array $cartItemIds
     * @param  boolean $ismMinus
     * @return boolean
     */
    public function updateQuantityProductWithCart($key, $cartItemIds, $ismMinus = true);


    /**
     * Take product less than 0
     *
     * @param  string $key (cart key or customer id)
     * @param  array $cartItemIds
     * @param  string $mark default < (<,=,>)
     * @return object
     */
    public function getNegativeProduct($key, $cartItemIds, $mark = '<');

    /**
     *
     * @param  int $productId
     * @return object
     */
    public function getProductClassByProductId($productId);

    /**
     *  Delete list product class
     * @param  array $productClassId
     * @return mixed
     */
    public function deleteProductClass(array $productClassId);
}
