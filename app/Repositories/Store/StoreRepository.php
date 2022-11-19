<?php

namespace App\Repositories\Store;

use App\Enums\EnumLiveStream;
use App\Enums\EnumProduct;
use App\Enums\EnumStaff;
use App\Enums\EnumStore;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\BankHistory;
use App\Models\Booking;
use App\Models\CalendarStaff;
use App\Models\Order;
use App\Models\Province;
use App\Models\RevenueOrder;
use App\Models\Staff;
use App\Models\Store;
use App\Models\Stripe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class StoreRepository extends BaseRepository implements StoreRepositoryInterface
{
    const PER_PAGE = 20;
    const PER_PAGE_IN_CMS = 10;

    public function getModel(): string
    {
        return Store::class;
    }

    /**
     * Get list of products.
     *
     * @param  array  $input
     * @param  array  $columns
     * @return LengthAwarePaginator
     */
    public function getList($request)
    {
        $tblStore = Store::getTableName();
        $tblProvince = Province::getTableName();
        $name = $request['name'] ?? null;
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;
        $tblStaff = Staff::getTableName();
        return $this->model
            ->select(
                "$tblStore.id",
                "$tblStore.name",
                "$tblStore.created_at",
                "$tblStore.phone",
                "$tblStaff.email as mail",
            )
            ->selectRaw("CONCAT($tblProvince.name,' ',city,' ',place,' ',COALESCE($tblStore.address,'')) as address")
            ->join($tblProvince, "$tblProvince.id", '=', "$tblStore.province_id")
            ->join($tblStaff, "$tblStaff.store_id", '=', "$tblStore.id")
            ->where("$tblStore.status", EnumStore::STATUS_CONFIRMED)
            ->where("$tblStaff.is_owner", EnumStaff::IS_OWNER)
            ->when($name, function ($query) use ($tblStore, $name) {
                return $query->where("$tblStore.name", 'like', "%$name%");
            })
            ->when($startDate, function ($query) use ($tblStore, $startDate) {
                return $query->whereDate("$tblStore.created_at", '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($tblStore, $endDate) {
                return $query->whereDate("$tblStore.created_at", '<=', $endDate);
            })
            ->withSum(
                'revenueOrders as revenue_store',
                'revenue_actual'
            )
            ->orderByDesc("$tblStore.created_at")
            ->orderBy("$tblStore.name")
            ->paginate(self::PER_PAGE_IN_CMS);
    }

    public function getStore($storeId)
    {
        $tblStore = Store::getTableName();
        return $this->model->select(
            "$tblStore.id",
            'status',
            'code',
            "$tblStore.name",
            "$tblStore.address as address_detail",
            DB::raw("commission * 100 as commission"),
            'description',
            'avatar',
            'cover_image'
        )
            ->where("$tblStore.id", $storeId)
            ->withCount([
                'products as total_product',
                'subOrders as total_order',
            ])
            // ->withSum(
            //     'revenueOrders as revenue_store',
            //     'revenue_actual'
            // )
            // ->withSum(
            //     'revenueOrders as revenue_total',
            //     'revenue'
            // )
            ->first();
    }

    public function searchStore($request)
    {
        $keyWord = $request['keyword'] ?? null;
        // $provinceId = $request['province_id'] ?? null;
        $perPage = $request['per_page'] ?? self::PER_PAGE;
        $paginate = $request['is_paginate'] ?? true;
        $tblStore = Store::getTableName();
        $query = $this->model->select(
            "$tblStore.id",
            "$tblStore.avatar",
            "$tblStore.name",
            "$tblStore.address",
            "$tblStore.description"
        )
            ->where("$tblStore.status", EnumStore::STATUS_CONFIRMED)
            ->when($keyWord, function ($query) use ($keyWord) {
                return $query->where('dtb_stores.name', 'like', '%' . $keyWord . '%');
            });
        // ->when($provinceId, function ($query) use ($provinceId) {
        //     return $query->whereIn('dtb_stores.province_id', $provinceId);
        // })
        if ($paginate) return $query->paginate($perPage);
        return $query->get();
    }

    public function getStoreInfo($storeId)
    {
        $tblProvince = Province::getTableName();
        $tblStore = Store::getTableName();
        return $this->model->select(
            "$tblStore.id",
            "$tblStore.name",
            'avatar',
            'cover_image',
            'phone',
            'time_start',
            'time_end',
            'work_day',
            'description'
        )
            ->selectRaw("concat($tblProvince.name,' ',city,' ',place,' ',COALESCE(address, '')) as address")
            ->join($tblProvince, 'province_id', '=', "$tblProvince.id")
            ->where("$tblStore.id", $storeId)
            ->first();
    }

    /**
     * Get total livestream of store.
     *
     * @param  int  $storeId
     * @param  array  $bookingStatusArr
     * @return mixed
     */
    public function getTotalLivestreamWithStatus(int $storeId, array $bookingStatusArr)
    {
        $tblStore = $this->model->getTableName();
        $tblStaff = Staff::getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblBooking = Booking::getTableName();

        return $this->model
            ->join($tblStaff, "$tblStore.id", '=', 'store_id')
            ->join($tblCalendarStaff, "$tblStaff.id", '=', 'staff_id')
            ->join($tblBooking, "$tblCalendarStaff.id", '=', 'calendar_staff_id')
            ->whereNull("$tblStaff.deleted_at")
            ->whereNull("$tblCalendarStaff.deleted_at")
            ->whereNull("$tblBooking.deleted_at")
            ->where('store_id', '=', $storeId)
            ->whereIn("$tblBooking.status", $bookingStatusArr)
            ->count('calendar_staff_id');
    }

    public function getListInstagram($request)
    {
        $perPage = $request['per_page'] ?? self::PER_PAGE;
        return $this->model
            ->select(
                'id',
                'name',
                'avatar',
                'link_instagram',
                'acc_stripe_id'
            )
            ->wherenotNull('link_instagram')
            ->with('stripe:id,person_stripe_id,surname,name')
            ->paginate($perPage);
    }

    public function getDetailBank($storeId)
    {
        $tblStore = Store::getTableName();
        $tblBankHistory = BankHistory::getTableName();
        $tblBank = Bank::getTableName();
        $tblBankBranch = BankBranch::getTableName();
        return $this->model
            ->select(
                "$tblBankHistory.bank_id",
                "$tblBankHistory.branch_id",
                "$tblBankHistory.customer_name",
                "$tblBankHistory.type",
                "$tblBankHistory.bank_number",
                "$tblBank.name as bank_name",
                "$tblBank.id as bank_id",
                "$tblBank.code as bank_code",
                "$tblBankBranch.name as branch_name",
                "$tblBankBranch.id as branch_id"
            )
            ->join($tblBankHistory, "$tblStore.bank_history_id_current", '=', "$tblBankHistory.id")
            ->leftJoin($tblBank, "$tblBank.id", '=', "$tblBankHistory.bank_id")
            ->leftJoin($tblBankBranch, "$tblBankBranch.id", '=', "$tblBankHistory.branch_id")
            ->where("$tblStore.id", $storeId)
            ->first();
    }

    public function findStoreByAccountStripe($accountId)
    {
        return $this->model
            ->where('acc_stripe_id', $accountId)
            ->first();
    }

    public function getStoreInGroupChat($arrayIds)
    {
        return $this->model
            ->select('id', 'name', 'avatar')
            ->selectRaw('id as storeId')
            ->whereIn('id', $arrayIds)
            ->get();
    }
}
