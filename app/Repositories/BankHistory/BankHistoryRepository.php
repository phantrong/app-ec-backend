<?php

namespace App\Repositories\BankHistory;

use App\Models\BankHistory;
use App\Models\Store;
use App\Repositories\BaseRepository;

class BankHistoryRepository extends BaseRepository implements BankHistoryRepositoryInterface
{

    public function getModel()
    {
        return BankHistory::class;
    }

    public function getStoreByStripeBankIds($stripeBanKIds = [], $columns = ['*'])
    {
        $tblStore = Store::getTableName();
        $tblBankHistory = $this->model->getTableName();

        return $this->model->leftJoin("$tblStore", "$tblStore.id", '=', "$tblBankHistory.store_id")
            ->when($stripeBanKIds, function ($query) use ($stripeBanKIds, $tblBankHistory) {
                return $query->whereIn("$tblBankHistory.external_account_id", $stripeBanKIds);
            })
            ->groupBy("$tblBankHistory.external_account_id")
            ->get($columns);
    }
}
