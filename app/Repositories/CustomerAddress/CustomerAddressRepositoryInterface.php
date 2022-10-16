<?php

namespace App\Repositories\CustomerAddress;

use App\Repositories\RepositoryInterface;

interface CustomerAddressRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     *
     * @param  integer $customerId
     * @param  array $data
     * @return bool
     */
    public function updateCustomerAddress($customerId, $data);
}
