<?php

namespace App\Repositories\Stripe;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Stripe\Collection;

interface StripeRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * Get list request upgrade.
     *
     * @param int $status
     * @return LengthAwarePaginator|Collection
     */
    public function getListAccountUpgrade($status, $paginate = true);
}
