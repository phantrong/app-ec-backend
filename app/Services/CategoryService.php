<?php

namespace App\Services;

use App\Enums\EnumBrand;
use App\Enums\EnumCategory;
use App\Repositories\Brand\BrandRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Product\ProductRepository;
use Illuminate\Support\Collection;

class CategoryService
{
    private CategoryRepository $categoryRepository;
    private ProductRepository $productRepository;
    private BrandRepository $brandRepository;

    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        BrandRepository $brandRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->brandRepository = $brandRepository;
    }

    public function getCategoryProductCount($request)
    {
        $storeId = $request['store_id'] ?? null;
        $products = $this->productRepository->getProductByFilter([
            'store_id' => $storeId
        ]);
        $productIds = $products->pluck('id')->all();
        $categoryIds = $products->pluck('category_id')->all();
        if (isset($request['category_id'])) {
            $categoryIds = $request['category_id'];
        }
        return $this->categoryRepository->getCategoryProductCount($productIds, $categoryIds);
    }

    public function getAllCategory($request)
    {
        return $this->categoryRepository->getAllCategory($request);
    }

    public function getCategoryBestSale(): Collection
    {
        $products = $this->productRepository->getTotalQuantityHasSaleOfProduct([]);
        return $this->categoryRepository->getCategoryBestSale($products);
    }

    public function getCategoryByStore($storeId)
    {
        $products = $this->productRepository->getProductByFilter([
            'store_id' => $storeId
        ]);
        $categoryIds = $products->pluck('category_id')->all();
        return $this->categoryRepository->getCategoryByIds($categoryIds);
    }

    public function createCategory(array $data)
    {
        return $this->categoryRepository->create($data);
    }

    public function updateCategory($categoryId, array $data)
    {
        return $this->categoryRepository->update($categoryId, $data);
    }

    public function checkCategoryUsed($categoryId): bool
    {
        $products = $this->productRepository->getProductByCategory($categoryId);
        return !$products->isEmpty();
    }

    public function deleteCategory($categoryId): bool
    {
        return $this->categoryRepository->delete($categoryId);
    }

    public function getListCategoryCMS(array $request)
    {
        $categories = $this->categoryRepository->getListCategoryCMS($request);
        $status = [
            EnumCategory::STATUS_PUBLIC => [
                'status' => EnumCategory::STATUS_PUBLIC,
                'total' => 0
            ],
            EnumCategory::STATUS_PRIVATE => [
                'status' => EnumCategory::STATUS_PRIVATE,
                'total' => 0
            ]
        ];
        foreach ($categories as $category) {
            $status[$category->status]['total']++;
        }
        return [
            'categories' => $categories,
            'status' => array_values($status)
        ];
    }

    public function getInfoCategory($categoryId, array $request)
    {
        $brands = $this->brandRepository->getBrandByCategory($categoryId, $request);
        $status = [
            EnumBrand::STATUS_PUBLIC => [
                'status' => EnumCategory::STATUS_PUBLIC,
                'total' => 0
            ],
            EnumBrand::STATUS_PRIVATE => [
                'status' => EnumCategory::STATUS_PRIVATE,
                'total' => 0
            ]
        ];
        foreach ($brands as $brand) {
            $status[$brand->status]['total']++;
        }
        return [
            'brands' => $brands,
            'status' => array_values($status)
        ];
    }
}
