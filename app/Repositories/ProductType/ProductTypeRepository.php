<?php

namespace App\Repositories\ProductType;

use App\Models\ProductType;
use App\Models\ProductTypeConfig;
use App\Repositories\BaseRepository;

class ProductTypeRepository extends BaseRepository implements ProductTypeRepositoryInterface
{
    public function getModel()
    {
        return ProductType::class;
    }

    public function getClassesOfProductClass($productClassId)
    {
        $tbProductType = ProductType::getTableName();
        $tbProductTypeConfig = ProductTypeConfig::getTableName();

        return $this->model->select("$tbProductTypeConfig.type_name", "$tbProductTypeConfig.name")
            ->join($tbProductTypeConfig, "$tbProductType.product_type_config_id", '=', "$tbProductTypeConfig.id")
            ->where("$tbProductType.product_class_id", $productClassId)
            ->get();
    }

    /**
     * get types product class
     *
     * @param  array $productClassIds
     * @return collections
     */
    public function getTypeProductClass($productClassIds)
    {
        $tblProductType = ProductType::getTableName();
        $tblPTypeConfig = ProductTypeConfig::getTableName();

        return $this->model->select(
            "$tblProductType.product_class_id",
            "$tblPTypeConfig.id",
            "$tblPTypeConfig.type_name",
            "$tblPTypeConfig.name"
        )
            ->join($tblPTypeConfig, "$tblProductType.product_type_config_id", '=', "$tblPTypeConfig.id")
            ->whereIn("$tblProductType.product_class_id", $productClassIds)
            ->get();
    }

    public function deleteProductTypeByProductClass($productClassId)
    {
        return $this->model
            ->where('product_class_id', $productClassId)
            ->delete();
    }
}
