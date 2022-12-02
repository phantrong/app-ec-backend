<?php

namespace App\Repositories\Category;

use App\Enums\EnumCategory;
use App\Enums\EnumProduct;
use App\Models\Category;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{

    const PERPAGE_HOME = 6;
    const CATEGORY_LIMIT = 4;

    public function getModel(): string
    {
        return Category::class;
    }

    public function getAllCategory($request): mixed
    {
        $perPage = $request['per_page'] ?? self::PERPAGE_HOME;
        return $this->model->select('id', 'name', 'image_path')
            ->get();
    }

    public function getCategoryProductCount($productIds, $categoryIds = []): mixed
    {
        $tableCategory = Category::getTableName();
        return $this->model->select(
            "$tableCategory.id",
            "$tableCategory.name"
        )
            ->when($categoryIds && $categoryIds[0], function ($query) use ($tableCategory, $categoryIds) {
                return $query->whereIn("$tableCategory.id", $categoryIds);
            })
            ->withCount([
                'products as total_product' => function ($query) use ($productIds) {
                    $query
                    // ->where('status', EnumProduct::STATUS_PUBLIC)
                        ->when($productIds, function ($query) use ($productIds) {
                            return $query->whereIn('id', $productIds);
                        });
                }
            ])
            ->get();
    }

    public function getCategoryBestSale($products): \Illuminate\Support\Collection
    {
        return $this->model
            ->leftJoinSub($products, 'products', function ($join) {
                $join->on('products.category_id', '=', 'mtb_categories.id');
            })
            ->select(
                'mtb_categories.id',
                'mtb_categories.name',
                DB::raw("SUM(products.total_product) as total_product")
            )
            ->whereNull('mtb_categories.deleted_at')
            ->groupBy('mtb_categories.id')
            ->orderByDesc('total_product')
            ->skip(0)
            ->limit(self::CATEGORY_LIMIT)
            ->get();
    }

    public function getCategoryByStore($storeId)
    {
        return $this->model->select(
            'id',
            'name'
        )
            ->withCount([
                'products as total_product' => function ($query) use ($storeId) {
                    $query->where('store_id', $storeId);
                }
            ])
            ->orderByDesc('total_product')
            ->having('total_product', '>', 0)
            ->get();
    }

    public function getListCategoryCMS($request)
    {
        $perPage = $request['per_page'] ?? self::PERPAGE_HOME;
        $name = $request['name'] ?? null;
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;
        $status = $request['status'] ?? null;
        return $this->model->select(
            'id',
            'name',
            'image_path',
            'created_at',
            'status'
        )
            ->where('id', '<>', EnumCategory::CATEGORY_OTHER_ID)
            ->when($name, function ($query) use ($name) {
                return $query->where('name', 'like', '%' . $name . '%');
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('created_at', '<=', $endDate);
            })
            ->orderByDesc('created_at')
            ->withCount("brands")
            ->paginate($perPage);
    }

    public function getCategoryByIds($categoryIds)
    {
        return $this->model->select(
            'id',
            'name'
        )
            ->whereIn('id', $categoryIds)
            ->get();
    }
}
