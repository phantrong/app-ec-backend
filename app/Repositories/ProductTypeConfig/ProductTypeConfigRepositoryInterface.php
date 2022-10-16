<?php

namespace App\Repositories\ProductTypeConfig;

use App\Repositories\RepositoryInterface;

interface ProductTypeConfigRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * get product config by type name
     *
     * @param int $productId
     * @param string $typeName
     * @return object
     */
    public function getProductTypeConfigByTypeName(int $productId, string $typeName);

    /**
     * delete list config
     *
     * @param array $configId
     * @return bool
     */
    public function deleteListConfig(array $configId);
}
