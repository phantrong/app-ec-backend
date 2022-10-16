<?php

namespace App\Repositories\BankHistory;

use App\Repositories\RepositoryInterface;

interface BankHistoryRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * getStoreByStripeBankIds
     *
     * @param  array $stripeBanKIds
     * @param  array $column
     * @return object
     */
    public function getStoreByStripeBankIds($stripeBanKIds = [], $column = ['*']);
}
