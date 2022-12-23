<?php

namespace App\Repositories\ProductMedia;

use App\Models\ProductMedia;
use App\Repositories\BaseRepository;

class ProductMediaRepository extends BaseRepository implements ProductMediaRepositoryInterface
{

    public function getModel(): string
    {
        return ProductMedia::class;
    }

    public function deleteProductMedia($productId)
    {
        return $this->model
            ->where('product_id', $productId)
            ->delete();
    }
}
