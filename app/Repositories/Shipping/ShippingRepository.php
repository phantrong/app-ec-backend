<?php

namespace App\Repositories\Shipping;

use App\Models\Shipping;
use App\Repositories\BaseRepository;

class ShippingRepository extends BaseRepository implements ShippingRepositoryInterface
{
    public function getModel()
    {
        return Shipping::class;
    }
}
