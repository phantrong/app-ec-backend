<?php

namespace App\Repositories\Customer;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    /**
     * Get customer list.
     *
     * @param array $condition
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getCustomerList(array $condition, $columns = ['*']);

    /**
     * Get customer detail by id.
     *
     * @param int $id
     * @param array $columns
     * @return Builder|Model|object
     */
    public function getCustomerDetail(int $id, $columns = ['*']);

    /**
     * Get customer in group chat.
     *
     * @param array $userId
     * @return Builder|Model|object
     */
    public function getUserInGroupChat($userId);

    /**
     * Get most field of customer by id and field.
     *
     * @param int $customerId
     * @param array $fields
     * @return Builder|Model|object
     */
    public function getInfoCustomer($customerId, $fields);
}
