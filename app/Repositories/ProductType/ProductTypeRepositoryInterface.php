<?php

namespace App\Repositories\ProductType;

use App\Repositories\RepositoryInterface;

interface ProductTypeRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * getClassesOfProductClass
     *
     * @param  int $productClassId
     * @return project
     */
    public function getClassesOfProductClass($productClassId);
}
