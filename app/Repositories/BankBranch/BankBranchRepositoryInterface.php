<?php

namespace App\Repositories\BankBranch;

use App\Repositories\RepositoryInterface;

interface BankBranchRepositoryInterface extends RepositoryInterface
{
    public function getModel();
}
