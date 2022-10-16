<?php

namespace App\Repositories\ProductTypeConfig;

use App\Models\ProductTypeConfig;
use App\Repositories\BaseRepository;

class ProductTypeConfigRepository extends BaseRepository implements ProductTypeConfigRepositoryInterface
{

    public function getModel(): string
    {
        return ProductTypeConfig::class;
    }

    public function getProductTypeConfigByTypeName($productId, $typeName)
    {
        return $this->model->where('product_id', $productId)
            ->where('type_name', $typeName)
            ->first();
    }

    public function deleteProductTypeConfig($productId)
    {
        return $this->model
            ->where('product_id', $productId)
            ->delete();
    }

    public function deleteListConfig(array $configId)
    {
        return $this->model
            ->whereIn('id', $configId)
            ->delete();
    }
}
