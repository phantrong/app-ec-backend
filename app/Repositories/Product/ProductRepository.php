<?php

namespace App\Repositories\Product;

use App\Enums\EnumProduct;
use App\Enums\EnumProductSort;
use App\Enums\EnumSubOrder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\ProductClass;
use App\Models\ProductFavorite;
use App\Models\Products;
use App\Models\RevenueProduct;
use App\Models\Store;
use App\Models\SubOrder;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    const PER_PAGE = 25;
    const OUT_OF_STOCK = 0;
    const PRODUCT_REFERENCE_LIMIT = 30;
    const PRODUCT_LIMIT = 8;
    const PRODUCT_CMS_LIMIT = 5;
    const LIMIT_PRODUCT_REVENUE = 20;

    public function getModel(): string
    {
        return Products::class;
    }

    public function searchProduct(array $request, $customerId, $isFavorite = null): mixed
    {
        $perPage = $request['per_page'] ?? self::PER_PAGE;
        $products = $this->getProductByFilter($request, $customerId, $isFavorite);
        $sortType = $request['sort'] ?? EnumProductSort::SORT_CREATED_AT;
        $paginate = $request['is_paginate'] ?? true;
        $tableProduct = Products::getTableName();
        switch ($sortType) {
            case EnumProductSort::SORT_PRICE_CHEAP:
                $products = $products->orderBy('product_price.min_discount');
                break;
            case EnumProductSort::SORT_PRICE_EXPENSIVE:
                $products = $products->orderByDesc('product_price.min_discount');
                break;
            case EnumProductSort::SORT_FAVORITE:
                $products = $products->orderByDesc('total_favorite');
                break;
            default:
                $products = $products->orderbyDesc("$tableProduct.created_at");
                break;
        }
        if (!$paginate || $isFavorite) {
            return $products->orderByDesc("$tableProduct.id")->get();
        }
        return $products->orderByDesc("$tableProduct.id")->paginate($perPage);
    }

    public function getProductByFilter($request, $customerId = null, $isFavorite = null)
    {
        $keyWord = $request['keyword'] ?? null;
        $productId = $request['product_id'] ?? [];
        $categoryId = $request['category_id'] ?? [];
        $brandId = $request['brand_id'] ?? [];
        $priceMin = $request['price_min'] ?? null;
        $priceMax = $request['price_max'] ?? null;
        $storeId = $request['store_id'] ?? null;
        $tableProduct = Products::getTableName();
        $tableStore = Store::getTableName();
        $tableProductFavorite = ProductFavorite::getTableName();
        return $this->model->select(
            "$tableProduct.id",
            "$tableProduct.name",
            "$tableProduct.price",
            "$tableProduct.description",
            "$tableProduct.discount",
            "$tableProduct.created_at",
            "$tableProduct.category_id",
            "$tableProduct.store_id",
            DB::raw("CASE WHEN DATEDIFF(now(), DATE($tableProduct.created_at)) > " .
                EnumProduct::DAY_PRODUCT_NEW . " THEN 0 ELSE 1 END as status")
        )
            ->join($tableStore, "$tableStore.id", '=', "$tableProduct.store_id")
            // ->where("$tableProduct.status", EnumProduct::STATUS_PUBLIC)
            ->when($keyWord, function ($query) use ($keyWord, $tableProduct) {
                return $query->where("$tableProduct.name", 'like', '%' . $keyWord . '%');
            })
            ->when($productId, function ($query) use ($productId, $tableProduct) {
                return $query->whereIn("$tableProduct.id", $productId);
            })
            ->when($categoryId, function ($query) use ($categoryId, $tableProduct) {
                return $query->whereIn("$tableProduct.category_id", $categoryId);
            })
            ->when($brandId, function ($query) use ($brandId, $tableProduct) {
                return $query->whereIn("$tableProduct.brand_id", $brandId);
            })
            ->when($priceMin !== null && $priceMax == null, function ($query) use ($priceMin, $tableProduct) {
                return $query->where(function ($query) use ($priceMin, $tableProduct) {
                    return $query->where("$tableProduct.discount", '>=', $priceMin);
                });
            })
            ->when($priceMax !== null && $priceMin == null, function ($query) use ($priceMax, $tableProduct) {
                return $query->where("$tableProduct.discount", '<=', $priceMax);
            })
            ->when($priceMin !== null && $priceMax !== null, function ($query) use ($priceMin, $priceMax, $tableProduct) {
                return $query->where(function ($query) use ($priceMin, $priceMax, $tableProduct) {
                    return  $query->whereBetween("$tableProduct.discount", [$priceMin, $priceMax]);
                });
            })
            ->when($storeId, function ($query) use ($storeId, $tableProduct) {
                return $query->where("$tableProduct.store_id", $storeId);
            })
            ->when(!$isFavorite, function ($query) use ($customerId) {
                return $query->with(['productFavorites' => function ($query) use ($customerId) {
                    return $query->select('id', 'product_id')->where('customer_id', $customerId);
                }]);
            })
            ->when($isFavorite, function ($query) use ($customerId, $tableProductFavorite, $tableProduct) {
                return $query->join($tableProductFavorite, "$tableProductFavorite.product_id", '=', "$tableProduct.id")
                    ->where("$tableProductFavorite.customer_id", $customerId);
            })
            ->with([
                'store:id,name',
                'category:id,name',
                'productMediasImage:product_id,media_path,media_type'
            ])
            ->withCount([
                'productFavorites as total_favorite'
            ]);
    }

    public function getPriceMinMaxProduct()
    {
        $tableProductClass = ProductClass::getTableName();
        return ProductClass::select(
            "$tableProductClass.product_id",
            DB::raw("MIN($tableProductClass.price) as min_price"),
            DB::raw("MAX($tableProductClass.price) as max_price"),
            DB::raw("MIN($tableProductClass.discount) as min_discount"),
            DB::raw("MAX($tableProductClass.discount) as max_discount"),
        )
            ->groupBy("$tableProductClass.product_id");
    }

    public function getTotalQuantityHasSaleOfProduct($request = [], $customerId = null)
    {
        $products = $this->getProductByFilter($request, $customerId);
        $tableProduct = Products::getTableName();
        $tableSubOrder = SubOrder::getTableName();
        $tableOrderItem = OrderItem::getTableName();
        return $products->join($tableOrderItem, "$tableOrderItem.product_id", '=', "$tableProduct.id")
            ->join($tableSubOrder, "$tableOrderItem.sub_order_id", '=', "$tableSubOrder.id")
            ->where("$tableSubOrder.status", EnumSubOrder::STATUS_SHIPPED)
            ->addSelect(DB::raw("SUM($tableOrderItem.quantity) as total_product"))
            ->groupBy("$tableProduct.id");
    }

    public function getProductBestSaleByCategory($request)
    {
        $perPage = $request['per_page'] ?? self::PER_PAGE;
        $categoryId = $request['category_id'] ?? [];
        $tableProduct = Products::getTableName();
        return $this->getTotalQuantityHasSaleOfProduct()
            ->when($categoryId, function ($query) use ($categoryId, $tableProduct) {
                return $query->whereIn("$tableProduct.category_id", $categoryId);
            })
            ->orderByDesc("total_product")
            ->paginate($perPage);
    }

    public function getDetailProduct($id, $customerId = null)
    {
        $tableProduct = Products::getTableName();
        return $this->model
            ->select(
                "$tableProduct.id",
                "$tableProduct.name",
                "$tableProduct.status",
                "$tableProduct.store_id",
                "$tableProduct.description",
                "$tableProduct.category_id",
                "$tableProduct.price",
                "$tableProduct.discount",
                DB::raw("CASE WHEN DATEDIFF(now(), DATE($tableProduct.created_at)) > " .
                    EnumProduct::DAY_PRODUCT_NEW . " THEN 0 ELSE 1 END as status")
            )
            ->where("$tableProduct.id", $id)
            ->where("$tableProduct.status", EnumProduct::STATUS_PUBLIC)
            ->with([
                'category:id,name',
                'store:id,name',
                'productMedias',
            ])
            ->with(['productFavorites' => function ($query) use ($customerId) {
                return $query->select('id', 'product_id')->where('customer_id', $customerId);
            }])
            ->first();
    }

    public function getProductReference($productId, $customerId)
    {
        $product = $this->model->find($productId);
        $tableProduct = Products::getTableName();
        if ($product) {
            $categoryId = $product->category_id;
            $brandId = $product->brand_id;
            return $this->getProductByFilter([], $customerId)
                ->where(function ($query) use ($categoryId, $brandId, $tableProduct) {
                    return $query->where("$tableProduct.category_id", $categoryId)
                        ->where("$tableProduct.brand_id", $brandId);
                })
                ->where("$tableProduct.id", '<>', $productId)
                ->limit(self::PRODUCT_REFERENCE_LIMIT)
                ->get();
        }
        return null;
    }

    public function getProductBestSaleByStore($request, $customerId)
    {
        return $this->getTotalQuantityHasSaleOfProduct($request, $customerId)
            ->orderByDesc("total_product")
            ->skip(0)
            ->take(self::PRODUCT_LIMIT)
            ->get();
    }

    public function getInfoProduct($productId)
    {
        $tableProduct = Products::getTableName();
        return $this->model->select(
            "$tableProduct.id",
            "$tableProduct.name",
            "$tableProduct.status",
            "$tableProduct.price",
            "$tableProduct.discount",
            'description',
            "$tableProduct.category_id",
        )
            ->with([
                'productMedias:id,product_id,media_type,media_path',
            ])
            ->where("$tableProduct.id", $productId)
            ->first();
    }

    public function getAllProductByStore($request, $storeId, $isCMS = false, $isPaginate = true)
    {
        $perPage = $request['per_page'] ?? ($isCMS ? self::PRODUCT_CMS_LIMIT : self::PRODUCT_LIMIT);
        $priceMin = $request['price_min'] ?? null;
        $priceMax = $request['price_max'] ?? null;
        $name = $request['name'] ?? null;
        $tableProduct = $this->model->getTableName();
        $products = $this->model->select(
            'id',
            'name',
            'store_id',
            'status',
            'price',
            'discount',
            'description',
        )
            ->when($name, function ($query) use ($name, $tableProduct) {
                return $query->where("$tableProduct.name", 'like', '%' . $name . '%');
            })
            ->when($priceMin !== null && $priceMax == null, function ($query) use ($priceMin) {
                return $query->where(function ($query) use ($priceMin) {
                    return $query->where('discount', '>=', $priceMin);
                });
            })
            ->when($priceMax !== null && $priceMin == null, function ($query) use ($priceMax) {
                return $query->where('discount', '<=', $priceMax);
            })
            ->when($priceMin !== null && $priceMax !== null, function ($query) use ($priceMin, $priceMax) {
                return $query->where(function ($query) use ($priceMin, $priceMax) {
                    return  $query->whereBetween('discount', [$priceMin, $priceMax]);
                });
            })
            ->when($storeId !== null, function ($query) use ($tableProduct, $storeId) {
                return $query->where("$tableProduct.store_id", '=', $storeId);
            })
            ->orderByDesc("$tableProduct.created_at")
            ->with([
                'productMedias:product_id,media_path',
                'store:id,name'
            ]);
        return $isPaginate ? $products->paginate($perPage) : $products->get();
    }

    public function getTotalProductClassHasSale()
    {
        $tableProductClass = ProductClass::getTableName();
        $tableOrderItem = OrderItem::getTableName();
        $tableSubOrder = SubOrder::getTableName();
        return ProductClass::selectRaw(
            "$tableProductClass.id,
            SUM($tableOrderItem.quantity) as total_product,
            SUM($tableOrderItem.quantity * $tableOrderItem.price) as revenue"
        )
            ->join($tableOrderItem, "$tableOrderItem.product_class_id", '=', "$tableProductClass.id")
            ->join($tableSubOrder, "$tableOrderItem.sub_order_id", '=', "$tableSubOrder.id")
            ->whereNull("$tableSubOrder.deleted_at")
            ->where("$tableSubOrder.status", EnumSubOrder::STATUS_SHIPPED)
            ->groupBy("$tableProductClass.id");
    }

    public function getAllRevenueByProduct($startDate, $endDate)
    {
        $tableSubOrder = SubOrder::getTableName();
        $tableOrderItem = OrderItem::getTableName();
        return OrderItem::selectRaw(
            "product_id, SUM(price * quantity) as revenue,
            SUM(quantity) as total_product, COUNT($tableOrderItem.id) as total_order"
        )
            ->join($tableSubOrder, "$tableSubOrder.id", '=', "$tableOrderItem.sub_order_id")
            ->where("$tableSubOrder.status", EnumSubOrder::STATUS_SHIPPED)
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('completed_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('completed_at', '<=', $endDate);
            })
            ->groupBy('product_id')
            ->orderBy('product_id');
    }

    public function getProductByCategory($categoryId)
    {
        return $this->model
            ->where('category_id', $categoryId)
            ->get();
    }

    /**
     * Get total product of store.
     *
     * @param int $storeId
     * @param array $productStatusArr
     * @return int
     */
    public function getTotalProductWithStatus(int $storeId, array $productStatusArr)
    {
        return $this->model
            ->where('store_id', '=', $storeId)
            ->whereIn('status', $productStatusArr)
            ->count();
    }

    public function getRevenueProductDaily($date = null, $storeId = null, $getName = false)
    {
        $startDate = $date ?: now()->subDay(1)->format('Y-m-d');
        $tableProduct = Products::getTableName();
        $now = now()->format('Y-m-d H:i:s');
        $productRevenue = $this->getAllRevenueByProduct($startDate, $startDate);
        return $this->model
            ->select(
                DB::raw("DATE('$startDate') as date_revenue"),
                "$tableProduct.id as product_id",
                'product_revenue.revenue',
                'product_revenue.total_order',
                'product_revenue.total_product',
                DB::raw(
                    "'$now' as create_at,
                    '$now' as update_at"
                )
            )
            ->joinSub($productRevenue, "product_revenue", function ($join) use ($tableProduct) {
                $join->on('product_revenue.product_id', '=', "$tableProduct.id");
            })
            ->when($storeId, function ($query) use ($tableProduct, $storeId) {
                return $query->where("$tableProduct.store_id", $storeId);
            })
            ->when($getName, function ($query) {
                return $query->addSelect('name');
            })
            ->orderByDesc('product_revenue.revenue')
            ->get();
    }

    public function getProductRevenueBest($startDate, $endDate, $storeId = null)
    {
        $tblProduct = Products::getTableName();
        $revenueProduct = RevenueProduct::selectRaw(
            "product_id,
            SUM(revenue) as revenue,
            SUM(total_product) as total_product,
            SUm(total_order) as total_order
            "
        )
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('date_revenue', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('date_revenue', '<=', $endDate);
            })
            ->groupBy('product_id');
        return $this->model
            ->select(
                'name',
                "$tblProduct.id as product_id",
                'revenue_product.revenue',
                'revenue_product.total_order',
                'revenue_product.total_product',
            )
            ->leftJoinSub($revenueProduct, 'revenue_product', function ($join) use ($tblProduct) {
                $join->on('revenue_product.product_id', '=', "$tblProduct.id");
            })
            ->when($storeId, function ($query) use ($storeId) {
                return $query->where('store_id', $storeId);
            })
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();
    }

    public function checkBrandUsed($brandId)
    {
        return $this->model
            ->where('brand_id', $brandId)
            ->exists();
    }

    public function getProductStockingByStore($storeId)
    {
        return 9999;
    }
}
