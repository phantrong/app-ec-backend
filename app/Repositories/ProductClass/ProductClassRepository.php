<?php

namespace App\Repositories\ProductClass;

use App\Enums\EnumProductClass;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductClass;
use App\Models\Products;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class ProductClassRepository extends BaseRepository implements ProductClassRepositoryInterface
{
    public function getModel(): string
    {
        return ProductClass::class;
    }

    public function updateQuantityProductWithCart($key, $cartItemIds, $ismMinus = true)
    {
        $tblProduct = Products::getTableName();
        $tblProductClass = ProductClass::getTableName();
        $tblCartItem = CartItem::getTableName();
        $tblCart = Cart::getTableName();
        $customerId = (int) $key;
        $mark = '+';

        if ($ismMinus) {
            $mark = '-';
        }

        return $this->model->select(
            "$tblProductClass.id",
            "$tblProductClass.stock",
            "$tblCartItem.quantity"
        )
            ->rightJoin("$tblCartItem", "$tblCartItem.product_classes_id", '=', "$tblProductClass.id")
            ->join("$tblProduct", "$tblProduct.id", '=', "$tblProductClass.product_id")
            ->leftJoin("$tblCart", "$tblCart.id", '=', "$tblCartItem.cart_id")
            ->where(function ($query) use ($tblCart, $key, $customerId) {
                $query->where("$tblCart.cart_key", "$key")
                    ->orWhere("$tblCart.customer_id", $customerId);
            })
            ->whereIn("$tblCartItem.id", $cartItemIds)
            ->whereNull("$tblProductClass.deleted_at")
            ->whereNull("$tblProduct.deleted_at")
            ->update([
                "$tblProductClass.stock" => DB::raw("{$tblProductClass}.stock {$mark} {$tblCartItem}.quantity"),
            ]);
    }

    public function getNegativeProduct($key, $cartItemIds, $mark = '<')
    {
        $tblProduct = Products::getTableName();
        $tblProductClass = ProductClass::getTableName();
        $tblCartItem = CartItem::getTableName();
        $tblCart = Cart::getTableName();
        $customerId = (int) $key;

        return $this->model->select(
            "$tblProductClass.id",
            "$tblProductClass.stock"
        )
            ->rightJoin("$tblCartItem", "$tblCartItem.product_classes_id", '=', "$tblProductClass.id")
            ->join("$tblProduct", "$tblProduct.id", '=', "$tblProductClass.product_id")
            ->leftJoin("$tblCart", "$tblCart.id", '=', "$tblCartItem.cart_id")
            ->where(function ($query) use ($tblCart, $key, $customerId) {
                $query->where("$tblCart.cart_key", "$key")
                    ->orWhere("$tblCart.customer_id", $customerId);
            })
            ->whereIn("$tblCartItem.id", $cartItemIds)
            ->whereNull("$tblProductClass.deleted_at")
            ->whereNull("$tblProduct.deleted_at")
            ->where("$tblProductClass.stock", "$mark", 0)
            ->get();
    }

    public function getProductClassByProductId($productId)
    {
        return $this->model->select(
            'id',
            'price',
            'discount',
            'stock'
        )
            ->where('dtb_product_classes.product_id', $productId)
            ->with('productTypeConfig:id,name,type_name')
            ->get();
    }

    public function checkProductHasConfig($productId)
    {
        return $this->model->where('product_id', $productId)
            ->where('has_type_config', EnumProductClass::HAS_CONFIG)
            ->first();
    }

    public function getProductClassByProduct($productId)
    {
        return $this->model
            ->where('product_id', $productId)
            ->get();
    }

    public function updateProductClassNotConfig(int $productId, array $dataProductClass)
    {
        return $this->model
            ->where('product_id', $productId)
            ->update($dataProductClass);
    }

    public function deleteProductClassNotConfig($productId)
    {
        return $this->model->where('product_id', $productId)->delete();
    }

    public function deleteProductClass(array $productClassId)
    {
        return $this->model
            ->whereIn('id', $productClassId)
            ->delete();
    }
}
