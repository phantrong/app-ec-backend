<?php

namespace App\Services;

use App\Enums\EnumProductClass;
use App\Models\ProductClass;
use App\Repositories\ProductClass\ProductClassRepository;
use App\Repositories\ProductType\ProductTypeRepository;
use Illuminate\Database\Eloquent\Model;

class ProductClassService
{
    private ProductClassRepository $productClassRepository;

    public function __construct(
        ProductClassRepository $productClassRepository,
        ProductTypeRepository  $productTypeRepository
    ) {
        $this->productClassRepository = $productClassRepository;
        $this->productTypeRepository = $productTypeRepository;
    }

    //create product class are classified
    public function createProductClassHasConfig(array $data, object $product)
    {
        $productTypeConfig = $product->productTypeConfig;
        foreach ($data as $value) {
            $value = json_decode($value, true);
            $this->createOneProductClassConfig($value, $product, $productTypeConfig);
        }
        return;
    }

    //create product class aren't classified
    public function createProductClassNotConfig(array $data, object $product)
    {
        return $product->productClasses()->save(
            new ProductClass([
                'status' => EnumProductClass::STATUS_ACTIVE,
                'has_type_config' => EnumProductClass::NO_HAS_CONFIG,
                'price' => $data['price'],
                'discount' => $data['discount'] ?? $data['price'],
                'stock' => $data['stock']
            ])
        );
    }

    public function createOneProductClassConfig(array $data, object $product, $productTypeConfig)
    {
        $productClass = $product->productClasses()->save(
            new ProductClass([
                'status' => $data['status'],
                'has_type_config' => EnumProductClass::HAS_CONFIG,
                'price' => $data['price'],
                'discount' => $data['discount'] ?? $data['price'],
                'stock' => $data['stock']
            ])
        );
        $timeStamp = [
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s')
        ];
        foreach ($data['type_name'] as $typeName) {
            foreach ($productTypeConfig as $type) {
                if ($type->type_name == $typeName) {
                    $productClass->productType()->attach($type->id, $timeStamp);
                }
            }
        }
        return;
    }

    public function updateProductClassHasConfig(array $data, object $product)
    {
        $productTypeConfig = $product->productTypeConfig;
        foreach ($data as $value) {
            $value = json_decode($value, true);
            // if product class exists
            $id = $value['id'] ?? 0;
            $value['discount'] = $value['discount'] ?: $value['price'];
            if ($id) {
                $this->productClassRepository->update($value['id'], [
                    'status' => $value['status'],
                    'has_type_config' => EnumProductClass::HAS_CONFIG,
                    'price' => $value['price'],
                    'discount' => $value['discount'],
                    'stock' => $value['stock']
                ]);
            } else {
                // if product class not exists
                $this->createOneProductClassConfig($value, $product, $productTypeConfig);
            }
        }
        return;
    }

    public function deleteProductClassByProduct($productId)
    {
        $productClasses = $this->productClassRepository->getProductClassByProduct($productId);
        foreach ($productClasses as $productClass) {
            $this->productTypeRepository->deleteProductTypeByProductClass($productClass->id);
            $productClass->delete();
        }
        return;
    }

    public function deleteProductClassNotConfig($productId)
    {
        return $this->productClassRepository->deleteProductClassNotConfig($productId);
    }

    public function updateProductClassNotConfig(array $dataProductClass, int $productId)
    {
        return $this->productClassRepository->updateProductClassNotConfig($productId, $dataProductClass);
    }

    public function deleteProductClass(array $productClassId)
    {
        return $this->productClassRepository->deleteProductClass($productClassId);
    }
}
