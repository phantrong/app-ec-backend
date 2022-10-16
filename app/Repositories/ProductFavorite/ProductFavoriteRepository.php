<?php

namespace App\Repositories\ProductFavorite;

use App\Models\ProductFavorite;
use App\Repositories\BaseRepository;

class ProductFavoriteRepository extends BaseRepository
{

    public function getModel()
    {
        return ProductFavorite::class;
    }

    public function likeProduct($customerId, $productId): bool
    {
        $result = $this->model
            ->where('product_id', $productId)
            ->where('customer_id', $customerId)
            ->delete();
        if (!$result) {
            $this->model->create([
                'customer_id' => $customerId,
                'product_id' => $productId
            ]);
        }
        return true;
    }
}
