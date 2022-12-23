<?php

namespace App\Services;

use App\Enums\EnumProduct;
use App\Models\ProductMedia;
use App\Repositories\ProductMedia\ProductMediaRepository;

class ProductMediaService
{

    private ProductMediaRepository $productMedia;

    public function __construct(ProductMediaRepository $productMediaRepository)
    {
        $this->productMedia = $productMediaRepository;
    }

    public function createProductMedia($images, $product)
    {
        foreach ($images as $image) {
            $product->productMedias()->save(
                new ProductMedia([
                    'media_path' => $image,
                    'media_type' => EnumProduct::MEDIA_TYPE_IMAGE
                ])
            );
        }
        return;
    }

    public function deleteProductMedia($productId)
    {
        return $this->productMedia->deleteProductMedia($productId);
    }
}
