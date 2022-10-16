<?php

namespace App\Repositories\BankBranch;

use App\Models\BankBranch;
use App\Repositories\BaseRepository;

class BankBranchRepository extends BaseRepository implements BankBranchRepositoryInterface
{
    public function getModel()
    {
        return BankBranch::class;
    }
}
