<?php

namespace App\Repositories\CartItem;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductClass;
use App\Repositories\BaseRepository;

class CartItemRepository extends BaseRepository implements CartItemRepositoryInterface
{
    public function getModel()
    {
        return CartItem::class;
    }

    public function createCarItem($cartId, $productId, $quantity)
    {
        $data = [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
        return $this->model->create($data);
    }

    public function forceDeletedCartItemByProduct($productId, $cartDelete)
    {
        $tblCartItem = CartItem::getTableName();
        $tblCart = Cart::getTableName();
        $customerId = (int)$cartDelete;

        return $this->model->rightJoin("$tblCart", "$tblCart.id", '=', "$tblCartItem.cart_id")
            ->where("$tblCartItem.product_id", $productId)
            ->where(function ($query) use ($tblCart, $cartDelete, $customerId) {
                $query->where("$tblCart.cart_key", "$cartDelete")
                    ->orWhere("$tblCart.customer_id", $customerId);
            })
            ->forceDelete();
    }

    public function forceDeleteCartItemIds($keyDelete, $cartItemIds)
    {
        $tblCartItem = CartItem::getTableName();
        $tblCart = Cart::getTableName();
        $customerId = (int)$keyDelete;

        return $this->model->rightJoin("$tblCart", "$tblCart.id", '=', "$tblCartItem.cart_id")
            ->whereIn("$tblCartItem.id", $cartItemIds)
            ->where(function ($query) use ($tblCart, $keyDelete, $customerId) {
                $query->where("$tblCart.cart_key", "$keyDelete")
                    ->orWhere("$tblCart.customer_id", $customerId);
            })
            ->forceDelete();
    }

    public function deleteCateItemByProduct($productId)
    {
        $tableCartItem = CartItem::getTableName();
        return $this->model
            ->where("$tableCartItem.product_id", $productId)
            ->delete();
    }
}
