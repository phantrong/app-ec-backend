<?php

namespace App\Repositories\RevenueProduct;

use App\Models\Products;
use App\Models\RevenueProduct;
use App\Repositories\BaseRepository;

class RevenueProductRepository extends BaseRepository implements RevenueProductRepositoryInterface
{
    const LIMIT_PRODUCT = 20;

    public function getModel()
    {
        return RevenueProduct::class;
    }
}
