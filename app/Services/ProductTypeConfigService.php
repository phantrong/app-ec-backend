<?php

namespace App\Services;

use App\Enums\EnumProductTypeConfig;
use App\Repositories\ProductTypeConfig\ProductTypeConfigRepository;

class ProductTypeConfigService
{
    private ProductTypeConfigRepository $productTypeConfig;

    public function __construct(ProductTypeConfigRepository $productTypeConfigRepository)
    {
        $this->productTypeConfig = $productTypeConfigRepository;
    }

    public function createProductTypeConfig($productId, $dataProductType)
    {
        $dataInsert = [];
        foreach ($dataProductType as $data) {
            $data = json_decode($data, true);
            $typeNames = $data['type_name'];
            foreach ($typeNames as $typeName) {
                $dataInsert[] = [
                    'product_id' => $productId,
                    'type' => EnumProductTypeConfig::TYPE_DEFAULT,
                    'name' => $data['name'],
                    'type_name' => $typeName,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ];
            }
        }
        return $this->productTypeConfig->insert($dataInsert);
    }

    public function updateProductTypeConfig($productId, array $dataProductType)
    {
        foreach ($dataProductType as $data) {
            $data = json_decode($data, true);
            // if product type exists
            $id = $data['id'] ?? null;
            if ($id) {
                $this->productTypeConfig->update($data['id'], $data);
            } else {
                // if product type not exists
                $data['product_id'] = $productId;
                $data['type'] = EnumProductTypeConfig::TYPE_DEFAULT;
                $this->productTypeConfig->create($data);
            }
        }
        return;
    }

    public function deleteProductTypeConfig($productId)
    {
        return $this->productTypeConfig->deleteProductTypeConfig($productId);
    }

    public function deleteListConfig(array $configId)
    {
        return $this->productTypeConfig->deleteListConfig($configId);
    }
}
