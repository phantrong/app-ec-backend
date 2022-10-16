<?php

namespace App\Repositories\Stripe;

use App\Enums\EnumStripe;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\BankHistory;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Store;
use App\Models\Stripe;
use App\Repositories\BaseRepository;

class StripeRepository extends BaseRepository implements StripeRepositoryInterface
{
    public function getModel()
    {
        return Stripe::class;
    }

    public function getListAccountUpgrade($status, $paginate = true)
    {
        $tblStore = Store::getTableName();
        $tblStripe = Stripe::getTableName();
        $tblCustomer = Customer::getTableName();
        $accounts = $this->model
            ->select(
                "$tblStripe.id",
                "$tblStore.id as store_id",
                "$tblStripe.surname",
                "$tblStripe.name",
                "$tblCustomer.email",
                "$tblCustomer.phone",
                "$tblStore.status"
            )
            ->join($tblStore, "$tblStore.acc_stripe_id", '=', "$tblStripe.person_stripe_id")
            ->join($tblCustomer, "$tblCustomer.id", '=', "$tblStore.customer_id")
            ->when($status, function ($query) use ($tblStore, $status) {
                return $query->where("$tblStore.status", $status);
            })
            ->orderBy("$tblStore.status")
            ->orderByDesc("$tblStripe.created_at");
        if ($paginate) {
            return $accounts->paginate(EnumStripe::PER_PAGE_LIST_ACCOUNT);
        }
        return $accounts->get();
    }

    public function detailAccountUpgrade($stripeId)
    {
        $tblStore = Store::getTableName();
        $tblStripe = Stripe::getTableName();
        $tblCustomer = Customer::getTableName();
        $tblProvince = Province::getTableName();
        $tblBankHistory = BankHistory::getTableName();
        $tblBank = Bank::getTableName();
        $tblBankBranch = BankBranch::getTableName();
        return $this->model
            ->select(
                "$tblStripe.id",
                "$tblStore.id as store_id",
                "$tblStore.company",
                "$tblStore.name as store_name",
                "$tblStore.postal_code as store_postal_code",
                "$tblStore.city as store_city",
                "$tblStore.place as store_place",
                "$tblStore.address as store_address",
                "$tblProvince.name as store_province",
                "$tblStore.fax",
                "$tblStore.status",
                "$tblStore.link",
                "$tblStore.phone as store_phone",
                "$tblStore.description",
                "$tblStripe.*",
                "$tblCustomer.email as customer_email",
                "$tblBankHistory.type as bank_type",
                "$tblBankHistory.bank_number",
                "$tblBankHistory.customer_name as bank_customer_name",
                "$tblBank.name as bank_name",
                "$tblBankBranch.name as branch_name"
            )
            ->join($tblStore, "$tblStore.acc_stripe_id", '=', "$tblStripe.person_stripe_id")
            ->join($tblCustomer, "$tblCustomer.id", '=', "$tblStore.customer_id")
            ->join($tblProvince, "$tblProvince.id", '=', "$tblStore.province_id")
            ->join($tblBankHistory, "$tblBankHistory.id", '=', "$tblStore.bank_history_id_current")
            ->join($tblBank, "$tblBank.id", '=', "$tblBankHistory.bank_id")
            ->leftJoin($tblBankBranch, "$tblBankBranch.id", '=', "$tblBankHistory.branch_id")
            ->where("$tblStripe.id", $stripeId)
            ->with('province:id,name')
            ->first();
    }
}
