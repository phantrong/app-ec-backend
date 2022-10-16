<?php

namespace App\Repositories\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\BaseRepository;

class CartRepository extends BaseRepository implements CartRepositoryInterface
{
    public function getModel()
    {
        return Cart::class;
    }

    public function getCartByKey($key)
    {
        $tblCart = Cart::getTableName();
        $customerId = (int)$key;

        return $this->model->where(function ($query) use ($tblCart, $key, $customerId) {
            $query->where("$tblCart.cart_key", "$key")
                ->orWhere("$tblCart.customer_id", $customerId);
        })
            ->first();
    }

    public function createCart($key)
    {
        $cartKey = $key;
        $customerId = null;
        if (is_int($key)) {
            $customerId = $key;
            $cartKey = null;
        }

        $data = [
            'cart_key' => $cartKey,
            'customer_id' => $customerId
        ];
        return $this->model->create($data);
    }

    public function getProductByKey($key)
    {
        $tblCart = Cart::getTableName();
        $customerId = (int)$key;

        return $this->model->with([
            'cartItem.productClassItem.product.store:id,name,status,avatar,code',
            'cartItem.productClassItem.product.productMediasImage',
            'cartItem.productClassItem.productTypeConfigs:type_name,name'
        ])
            ->where(function ($query) use ($tblCart, $key, $customerId) {
                $query->where("$tblCart.cart_key", "$key")
                    ->orWhere("$tblCart.customer_id", $customerId);
            })
            ->first();
    }

    public function getProductUseCreateOrder($key, $cartItemIds)
    {
        $tblCart = Cart::getTableName();
        $tblCartItem = CartItem::getTableName();
        $customerId = (int)$key;

        return $this->model->with([
            'cartItem' => function ($q) use ($tblCartItem, $cartItemIds) {
                $q->whereIn("$tblCartItem.id", $cartItemIds);
            },
            'cartItem.productClassItem.product.store:id,name,status,avatar,code,commission',
        ])
            ->where(function ($query) use ($tblCart, $key, $customerId) {
                $query->where("$tblCart.cart_key", "$key")
                    ->orWhere("$tblCart.customer_id", $customerId);
            })
            ->first();
    }

    public function getCartOverView($key)
    {
        return $this->model->select('id')
            ->where('cart_key', $key)
            ->withSum('cartItem as total_product', 'quantity')
            ->with([
                'cartItem:id,cart_id,product_classes_id,quantity',
                'cartItem.productClassItem:id,product_id,price,discount',
                'cartItem.productClassItem.product:id,name',
                'cartItem.productClassItem.productTypeConfigs:type_name,name',
                'cartItem.productClassItem.product.productMedias:product_id,media_path'
            ])
            ->get();
    }
}
