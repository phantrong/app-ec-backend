<?php

namespace App\Repositories\Brand;

use App\Repositories\RepositoryInterface;

interface BrandRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all
     * count products of brand
     *
     * @param array $productIds
     * @param array $categoryIds
     * @param array $brandIds
     * @return mixed
     */
    public function getBrandProductCount($productIds, $categoryIds = [], $brandIds = []): object;
}
