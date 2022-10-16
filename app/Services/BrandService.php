<?php

namespace App\Services;

use App\Enums\EnumBrand;
use App\Enums\EnumCategory;
use App\Repositories\Brand\BrandRepository;
use App\Repositories\Product\ProductRepository;

class BrandService
{
    private BrandRepository $brandRepository;
    private ProductRepository $productRepository;

    public function __construct(BrandRepository $brandRepository, ProductRepository $productRepository)
    {
        $this->brandRepository = $brandRepository;
        $this->productRepository = $productRepository;
    }

    public function getBrandProductCount($request)
    {
        $brandIds = [];
        $products = $this->productRepository->getProductByFilter($request);
        $categoryIds = $products->pluck('category_id')->all();
        $productIds = $products->pluck('id')->all();
        if (isset($request['store_id'])) {
            $brandIds = $products->pluck('brand_id')->all();
        }
        if (isset($request['category_id']) && $request['category_id']) {
            $categoryIds = $request['category_id'];
        }
        $brands = $this->brandRepository->getBrandProductCount($productIds, $categoryIds, $brandIds)->toArray();
        $totalProductBrand = array_sum(array_column($brands, 'total_product'));
        $productBrandOther = count($productIds) - $totalProductBrand;
        if ($productBrandOther) {
            $brandOther = [
                'id' => EnumBrand::BRAND_OTHER_ID,
                'category_id' => EnumCategory::CATEGORY_IGNORE,
                'name' => EnumBrand::BRAND_OTHER_NAME,
                'total_product' => $productBrandOther
            ];
            array_unshift($brands, $brandOther);
        }
        return $brands;
    }

    public function getAllBrand($request)
    {
        return $this->brandRepository->getAllBrand($request);
    }

    public function createBrand(array $data)
    {
        return $this->brandRepository->create($data);
    }

    public function deleteBrand($brandId)
    {
        return $this->brandRepository->delete($brandId);
    }

    public function updateBrand($brandId, array $data)
    {
        return $this->brandRepository->update($brandId, $data);
    }

    public function checkBrandUsed($brandId)
    {
        return $this->productRepository->checkBrandUsed($brandId);
    }
}
