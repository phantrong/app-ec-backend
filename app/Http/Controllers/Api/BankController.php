<?php

namespace App\Http\Controllers\Api;

use App\Models\BankBranch;
use App\Services\BankService;

class BankController extends BaseController
{
    private BankService $bankService;

    public function __construct(
        BankService $bankService
    ) {
        $this->bankService = $bankService;
    }

    public function getBankList()
    {
        $columns = [
            'id',
            'code',
            'name',
        ];

        return $this->sendResponse($this->bankService->getBankList($columns));
    }

    public function getBankBranchList($bankId)
    {
        $tblBankBranch = BankBranch::getTableName();

        $columns = [
            "$tblBankBranch.id",
            "$tblBankBranch.code",
            "$tblBankBranch.name",
        ];
        return $this->sendResponse($this->bankService->getBankBranchList($bankId, $columns));
    }
}
