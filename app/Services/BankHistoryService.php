<?php

namespace App\Services;

use App\Repositories\BankHistory\BankHistoryRepository;

class BankHistoryService
{
    private BankHistoryRepository $bankHistoryRepository;

    public function __construct(BankHistoryRepository $bankHistoryRepository)
    {
        $this->bankHistoryRepository = $bankHistoryRepository;
    }

    public function createBankHistory($data)
    {
        return $this->bankHistoryRepository->create($data);
    }
}
