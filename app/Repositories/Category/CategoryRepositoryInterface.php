<?php

namespace App\Repositories\Category;

use App\Repositories\RepositoryInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all
     * get category by filter
     *
     * @param array $request
     * @return mixed
     */
    public function getAllCategory(array $request): mixed;

    /**
     * Get all
     * count products of category
     *
     * @param array $productIds
     * @param array $categoryIds
     * @return mixed
     */
    public function getCategoryProductCount(array $productIds, array $categoryIds = []): mixed;

    /**
     * getCategoryByIds
     *
     * @param  array $categoryIds
     * @return object
     */
    public function getCategoryByIds($categoryIds);
}
