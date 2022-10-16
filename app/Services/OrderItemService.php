<?php

namespace App\Services;

use App\Repositories\OrderItem\OrderItemRepository;
use App\Repositories\ProductType\ProductTypeRepository;

class OrderItemService
{
    private $orderItemRepository;
    private $productTypeRepository;

    public function __construct(
        OrderItemRepository $orderItemRepository,
        ProductTypeRepository $productTypeRepository
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->productTypeRepository = $productTypeRepository;
    }
}
