<?php

namespace App\Repositories\ProductMedia;

use App\Repositories\RepositoryInterface;

interface ProductMediaRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * delete product medias
     *
     * @param int $productId
     * @return mixed
     */
    public function deleteProductMedia(int $productId);
}
