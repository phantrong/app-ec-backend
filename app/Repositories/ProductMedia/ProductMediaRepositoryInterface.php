<?php

namespace App\Repositories\ProductMedia;

use App\Repositories\RepositoryInterface;

interface ProductMediaRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * delete product medias
     *
     * @param array $arrayIds
     * @return mixed
     */
    public function deleteProductMedia(array $arrayIds);
}
