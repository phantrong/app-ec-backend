<?php

namespace App\Repositories\Payout;

use App\Enums\EnumStripe;
use App\Models\Payout;
use App\Models\Store;
use App\Repositories\BaseRepository;
use Carbon\Carbon;

class PayoutRepository extends BaseRepository implements PayoutRepositoryInterface
{

    const PER_PAGE_PAYOUT_HISTORY = 11;

    public function getModel()
    {
        return Payout::class;
    }

    public function findByPayoutId($payoutId)
    {
        return $this->model->where('stripe_payout_id', $payoutId)->first();
    }

    public function getArrStatusByInt($status)
    {
        switch ($status) {
            case EnumStripe::STATUS_PAYOUT_HISTORY_PAID:
                $txtStatus = ['paid'];
                break;
            case EnumStripe::STATUS_PAYOUT_HISTORY_PENDING:
                $txtStatus = ['pending', 'in_transit'];
                break;
            case EnumStripe::STATUS_PAYOUT_HISTORY_FAILED:
                $txtStatus = ['failed'];
                break;
            default:
                $txtStatus = [];
                break;
        }
        return $txtStatus;
    }

    public function getAllPayout($request, $columns = ['*'], $paginate = true)
    {
        $tblStore = Store::getTableName();
        $tblPayout = $this->model::getTableName();

        $perPage = static::PER_PAGE_PAYOUT_HISTORY;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $storeName = $request->store_name;
        $status = $this->getArrStatusByInt($request->status);

        $payouts = $this->model->select($columns)
            ->selectRaw(
                "(
                    CASE
                        WHEN $tblPayout.status = 'paid' THEN ".EnumStripe::STATUS_PAYOUT_HISTORY_PAID."
                        WHEN $tblPayout.status = 'pending' OR $tblPayout.status = 'in_transit'
                        THEN ".EnumStripe::STATUS_PAYOUT_HISTORY_PENDING."
                        WHEN $tblPayout.status = 'failed' THEN ".EnumStripe::STATUS_PAYOUT_HISTORY_FAILED."
                    ELSE 0 END
                ) as status"
            )
            ->when($startDate, function ($query) use ($startDate, $tblPayout) {
                return $query->where(
                    "$tblPayout.arrival_date",
                    '>=',
                    Carbon::parse($startDate)->setHour(0)->setMinute(0)->setSecond(0)->timestamp
                );
            })
            ->when($endDate, function ($query) use ($endDate, $tblPayout) {
                return $query->where(
                    "$tblPayout.arrival_date",
                    '<=',
                    Carbon::parse($endDate)->setHour(23)->setMinute(59)->setSecond(59)->timestamp
                );
            })
            ->when($status, function ($query) use ($status, $tblPayout) {
                return $query->whereIn("$tblPayout.status", $status);
            })
            ->when($storeName, function ($query) use ($storeName, $tblStore) {
                return $query->where("$tblStore.name", 'like', '%'.$storeName.'%');
            })
            ->leftJoin("$tblStore", "$tblStore.acc_stripe_id", '=', "$tblPayout.stripe_account_id")
            ->orderByDesc("$tblPayout.id");
        if ($paginate) {
            return $payouts->paginate($perPage);
        }
        return $payouts->get();
    }

    public function getPayoutDetailCMS($payoutId, $columns = ['*'])
    {
        $tblStore = Store::getTableName();
        $tblPayout = $this->model::getTableName();

        return $this->model->with([
            'bankHistory:id,external_account_id,bank_id,branch_id,type,bank_number,customer_name',
            'bankHistory.bank',
            'bankHistory.bankBranch',
        ])
            ->select($columns)
            ->selectRaw(
                "(
                    CASE
                        WHEN $tblPayout.status = 'paid' THEN " . EnumStripe::STATUS_PAYOUT_HISTORY_PAID . "
                        WHEN $tblPayout.status = 'pending' OR $tblPayout.status = 'in_transit'
                        THEN " . EnumStripe::STATUS_PAYOUT_HISTORY_PENDING . "
                        WHEN $tblPayout.status = 'failed' THEN " . EnumStripe::STATUS_PAYOUT_HISTORY_FAILED . "
                    ELSE 0 END
                ) as status"
            )
            ->leftJoin("$tblStore", "$tblStore.acc_stripe_id", '=', "$tblPayout.stripe_account_id")
            ->where("$tblPayout.id", $payoutId)
            ->first();
    }
}
