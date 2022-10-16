<?php

namespace App\Services;

use App\Repositories\Shipping\ShippingRepository;

class ShippingService
{
    private $shippingRepository;

    public function __construct(
        ShippingRepository $shippingRepository
    ) {
        $this->shippingRepository = $shippingRepository;
    }
}
