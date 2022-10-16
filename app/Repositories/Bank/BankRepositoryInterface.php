<?php

namespace App\Repositories\Bank;

use App\Repositories\RepositoryInterface;

interface BankRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    public function getBankList($columns = ['*']);

    /**
     * getBankBranchList
     *
     * @param  string $branchCode
     * @param  array $columns
     * @return collections
     */
    public function getBankBranchList($branchCode, $columns = ['*']);
}
