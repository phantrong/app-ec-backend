<?php

namespace App\Repositories\Bank;

use App\Models\Bank;
use App\Models\BankBranch;
use App\Repositories\BaseRepository;

class BankRepository extends BaseRepository implements BankRepositoryInterface
{

    public function getModel()
    {
        return Bank::class;
    }

    public function getBankList($columns = ['*'])
    {
        return $this->model
            ->orderBy('full_width_kana')
            ->get($columns);
    }

    public function getBankBranchList($bankId, $columns = ['*'])
    {
        $tblBankBranch = BankBranch::getTableName();
        $tblBank = Bank::getTableName();

        return BankBranch::join($tblBank, "$tblBank.code", '=', "$tblBankBranch.bank_code")
            ->where("$tblBank.id", $bankId)
            ->orderBy("$tblBankBranch.full_width_kana")
            ->get($columns);
    }
}
