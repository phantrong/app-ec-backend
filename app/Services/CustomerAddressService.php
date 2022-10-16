<?php

namespace App\Services;

use App\Repositories\CustomerAddress\CustomerAddressRepository;

class CustomerAddressService
{
    private CustomerAddressRepository $customerAddress;

    public function __construct(CustomerAddressRepository $customerAddressRepository)
    {
        $this->customerAddress = $customerAddressRepository;
    }

    public function createCustomerAddress($data)
    {
        return $this->customerAddress->create($data);
    }

    public function updateCustomerAddress($customerId, array $data)
    {
        return $this->customerAddress->updateCustomerAddress($customerId, $data);
    }
}
