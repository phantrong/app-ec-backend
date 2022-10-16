<?php

namespace App\Repositories\CustomerAddress;

use App\Models\CustomerAddress;
use App\Repositories\BaseRepository;

class CustomerAddressRepository extends BaseRepository implements CustomerAddressRepositoryInterface
{

    public function getModel()
    {
        return CustomerAddress::class;
    }

    public function updateCustomerAddress($customerId, $data)
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->update($data);
    }
}
