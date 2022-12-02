<?php

namespace App\Repositories\Brand;

use App\Enums\EnumBrand;
use App\Enums\EnumProduct;
use App\Models\Brand;
use App\Repositories\BaseRepository;

class BrandRepository extends BaseRepository implements BrandRepositoryInterface
{
    const PER_PAGE = 10;

    public function getModel(): string
    {
        return Brand::class;
    }

    public function getBrandProductCount($productIds, $categoryIds = [], $brandIds = []): object
    {
        $tableBrand = Brand::getTableName();
        return $this->model->select(
            "$tableBrand.id",
            "$tableBrand.name",
            "$tableBrand.category_id"
        )
            ->withCount([
                'products as total_product' => function ($query) use ($productIds) {
                    $query
                    // ->where('status', EnumProduct::STATUS_PUBLIC)
                        ->when($productIds, function ($query) use ($productIds) {
                            return $query->whereIn('id', $productIds);
                        });
                }
            ])
            ->when($brandIds, function ($query) use ($brandIds, $tableBrand) {
                return $query->whereIn("$tableBrand.id", $brandIds);
            })
            ->when($categoryIds && $categoryIds[0], function ($query) use ($categoryIds, $tableBrand) {
                return $query->whereIn("$tableBrand.category_id", $categoryIds);
            })
            ->get();
    }

    public function getAllBrand($request)
    {
        $perPage = $request['per_page'] ?? self::PER_PAGE;
        $categoryId = $request['category_id'] ?? null;
        $paginate = $request['is_paginate'] ?? true;
        if ($paginate) return $this->model->select('id', 'name')
            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->paginate($perPage);
        return $this->model->select('id', 'name')
            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->get();
    }

    public function getBrandByCategory($categoryId, $request = [])
    {
        $startDate = $request['start_date'] ?? null;
        $status = $request['status'] ?? null;
        $name = $request['name'] ?? null;
        $endDate = $request['end_date'] ?? null;
        $perPage = $request['per_page'] ?? self::PER_PAGE;
        return $this->model->select(
            'id',
            'name',
            'status',
            'created_at'
        )
            ->where('category_id', $categoryId)
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
            ->paginate($perPage);
    }
}
