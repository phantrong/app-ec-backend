<?php

namespace App\Repositories\Payout;

use App\Repositories\RepositoryInterface;

interface PayoutRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     *
     * @param  string $payoutId
     * @return collection
     */
    public function findByPayoutId($payoutId);


    public function getAllPayout($request, $columns = ['*'], $paginate = true);


    public function getPayoutDetailCMS($payoutId, $columns = ['*']);
}
