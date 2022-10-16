<?php

namespace App\Services;

use App\Repositories\Bank\BankRepository;

class BankService
{
    private BankRepository $bankRepository;

    public function __construct(BankRepository $bankRepository)
    {
        $this->bankRepository = $bankRepository;
    }

    public function getBankList($columns)
    {
        return $this->bankRepository->getBankList($columns);
    }

    public function getBankBranchList($bankId, $columns)
    {
        return $this->bankRepository->getBankBranchList($bankId, $columns);
    }
}
