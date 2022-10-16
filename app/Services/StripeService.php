<?php

namespace App\Services;

use App\Enums\EnumStore;
use App\Enums\EnumStripe;
use App\Enums\EnumBankHistory;
use App\Models\BankHistory;
use App\Models\Payout;
use App\Models\Store;
use App\Repositories\Bank\BankRepository;
use App\Repositories\BankBranch\BankBranchRepository;
use App\Repositories\BankHistory\BankHistoryRepository;
use App\Repositories\Payout\PayoutRepository;
use App\Repositories\Store\StoreRepository;
use App\Repositories\Stripe\StripeRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\False_;
use Stripe\StripeClient;

class StripeService
{

    private StripeRepository $stripeRepository;
    private BankHistoryRepository $bankHistoryRepository;
    private PayoutRepository $payoutRepository;
    private StoreRepository $storeRepository;
    private BankRepository $bankRepository;
    private BankBranchRepository $bankBranchRepository;

    public function __construct(
        StripeRepository $stripeRepository,
        BankHistoryRepository $bankHistoryRepository,
        PayoutRepository $payoutRepository,
        StoreRepository $storeRepository,
        BankRepository $bankRepository,
        BankBranchRepository $bankBranchRepository
    ) {
        $this->stripeRepository = $stripeRepository;
        $this->bankHistoryRepository = $bankHistoryRepository;
        $this->payoutRepository = $payoutRepository;
        $this->storeRepository = $storeRepository;
        $this->bankRepository = $bankRepository;
        $this->bankBranchRepository = $bankBranchRepository;
    }

    public function createStripe(array $data)
    {
        return $this->stripeRepository->create($data);
    }

    public function getListAccountUpgrade($status)
    {
        return $this->stripeRepository->getListAccountUpgrade($status);
    }

    public function countStatusListAccount($status)
    {
        $accountAll = $this->stripeRepository->getListAccountUpgrade($status, false);
        $arrayStatus = [
            EnumStore::STATUS_NEW => [
                'status' => EnumStore::STATUS_NEW,
                'quantity' => 0
            ],
            EnumStore::STATUS_CANCEL => [
                'status' => EnumStore::STATUS_CANCEL,
                'quantity' => 0
            ],
            EnumStore::STATUS_CONFIRMED => [
                'status' => EnumStore::STATUS_CONFIRMED,
                'quantity' => 0
            ],
            EnumStore::STATUS_WAITING_STRIPE => [
                'status' => EnumStore::STATUS_WAITING_STRIPE,
                'quantity' => 0
            ],
            EnumStore::STATUS_FAIL => [
                'status' => EnumStore::STATUS_FAIL,
                'quantity' => 0
            ],
        ];
        foreach ($accountAll as $account) {
            $arrayStatus[$account->status]['quantity']++;
        }
        return array_values($arrayStatus);
    }

    public function updateStripe($stripeId, array $data)
    {
        return $this->stripeRepository->update($stripeId, $data);
    }

    public function detailAccountUpgrade($stripeId)
    {
        return $this->stripeRepository->detailAccountUpgrade($stripeId);
    }

    public function createAccount($stripe, $clientIp)
    {
        $phone = EnumStripe::AREA_CODE_JP . substr($stripe->phone, 1);
        $stripeClient = new StripeClient(config('stripe.secret_key'));
        $birthday = Carbon::parse($stripe->birthday);
        return $stripeClient->accounts->create([
            'type' => 'custom',
            'country' => 'JP',
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            "business_type" => "individual",
            "business_profile" => [
                "mcc" => "5734",
                "product_description" => "connect account",
                "url" => config('services.link_service_front')
            ],
            'individual' => [
                "address_kana" => [
                    "country" => "JP",
                    "postal_code" => $stripe->postal_code,
                    "state" => $stripe->province->name_kana,
                    "city" => $stripe->city_kana,
                    "town" => $stripe->place_kana,
                    "line1" => $stripe->place_kana,
                    "line2" => $stripe->address_kana ?: $stripe->place_kana,
                ],
                "address_kanji" => [
                    "country" => "JP",
                    "postal_code" => $stripe->postal_code,
                    "state" => $stripe->province->name,
                    "city" => $stripe->city,
                    "town" => $stripe->place,
                    "line1" => $stripe->place,
                    "line2" => $stripe->address ?: $stripe->place,
                ],

                "dob" => [
                    "day" => $birthday->day,
                    "month" => $birthday->month,
                    "year" => $birthday->year,
                ],
                "first_name_kanji" => $stripe->first_name,
                "last_name_kanji" => $stripe->last_name,
                "first_name_kana" => $stripe->first_name_furigana,
                "last_name_kana" => $stripe->last_name_furigana,
                "phone" => $phone,
                "email" => $stripe->customer->email,
                'verification' => [
                    'document' => [
                        'back' => $stripe->image_back_id,
                        'front' => $stripe->image_front_id,
                    ]
                ]
            ],

            'tos_acceptance' => [
                'date' => time(),
                'ip' => $clientIp,
            ],

            "settings" => [
                'card_payments' => [
                    'decline_on' => [
                        "avs_failure" => false,
                        "cvc_failure" => false,
                    ]
                ],

                "payouts" => [
                    "schedule" => [
                        "monthly_anchor" => 31,
                        "interval" => "monthly"
                    ]
                ]
            ],
        ]);
    }

    public function createBankAccountToKen($dataBank)
    {
        $bank = $this->bankRepository->find($dataBank['bank_id']);
        if ($bank->name == EnumStripe::BANK_TEST) {
            $routingNumber = EnumStripe::ROUTING_TEST;
        } else {
            $branch = $this->bankBranchRepository->find($dataBank['branch_id']);
            $bankCode = $bank->code ?? null;
            $branchCode = $branch->code ?? null;
            $routingNumber = $bankCode . $branchCode;
        }
        if ($routingNumber) {
            $stripe = new StripeClient(config('stripe.secret_key'));
            return $stripe->tokens->create([
                'bank_account' => [
                    'country' => 'JP',
                    'currency' => 'JPY',
                    'account_holder_name' => $dataBank['customer_name'],
                    'account_holder_type' => $dataBank['type'] == EnumBankHistory::TYPE_INDIVIDUAL ?
                        'individual' :
                        'company',
                    'routing_number' => $routingNumber,
                    'account_number' => $dataBank['bank_number'],
                ],
            ]);
        }
        return false;
    }

    public function createExternalAccount($accountId, $bankTokenId)
    {
        $stripe = new StripeClient(config('stripe.secret_key'));
        return $stripe->accounts->createExternalAccount(
            $accountId,
            [
                'external_account' => $bankTokenId,
            ]
        );
    }

    public function createExternalAccountDefault($accountId, $bankTokenId)
    {
        $stripe = new StripeClient(config('stripe.secret_key'));
        return $stripe->accounts->createExternalAccount(
            $accountId,
            [
                'external_account' => $bankTokenId,
                'default_for_currency' => true
            ]
        );
    }

    public function deleteExternalAccount($accountId, $bankTokenId)
    {
        $stripe = new StripeClient(config('stripe.secret_key'));
        return $stripe->accounts->deleteExternalAccount(
            $accountId,
            $bankTokenId
        );
    }


    public function uploadImageCard($image)
    {
        $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
        $imagePath = str_replace('public/', 'storage/', $image);
        $fp = fopen($imagePath, 'r');
        $stripe = $stripe->files->create([
            'purpose' => 'identity_document',
            'file' => $fp
        ]);
        return $stripe->id;
    }

    /**
     *
     * @param  string $stripeId
     * @return collection
     */
    public function getPayoutHistoryStore($stripeId, $request)
    {
        $startingAfter = $request->page == 1 ? null : $request->page;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $param = ['limit' => EnumStripe::PER_PAGE_PAYOUT_HISTORY];

        if ($startingAfter) {
            $param['starting_after'] = $startingAfter;
        }
        if ($startDate) {
            $param['arrival_date'] = ['gte' =>
            Carbon::parse($startDate)->setHour(0)->setMinute(0)->setSecond(0)
                ->timestamp];
        }
        if ($endDate) {
            if (isset($param['arrival_date'])) {
                $param['arrival_date']['lte'] =
                    Carbon::parse($endDate)->setHour(23)->setMinute(59)->setSecond(59)
                    ->timestamp;
            } else {
                $param['arrival_date'] = ['lte' =>
                Carbon::parse($endDate)->setHour(23)->setMinute(59)->setSecond(59)
                    ->timestamp];
            }
        }
        try {
            $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
            $payoutHistory = $stripe->payouts->all($param, ['stripe_account' => $stripeId]);
            return $this->formatPayoutHistoryResponse($payoutHistory);
        } catch (\Exception $e) {
            return;
        }
    }

    private function getStatusPayoutHistory($status)
    {

        switch ($status) {
            case 'paid':
                $result = EnumStripe::STATUS_PAYOUT_HISTORY_PAID;
                break;
            case 'pending':
            case 'in_transit':
                $result = EnumStripe::STATUS_PAYOUT_HISTORY_PENDING;
                break;
            default:
                $result = EnumStripe::STATUS_PAYOUT_HISTORY_FAILED;
                break;
        }
        return $result;
    }

    private function formatPayoutHistoryResponse($payoutHistory)
    {
        if (!empty($payoutHistory['data'])) {
            $dataResult = null;
            foreach ($payoutHistory['data'] as $data) {
                $dataResult[] = [
                    'id' => $data->id,
                    'arrival_date' => $data->arrival_date,
                    'created' => $data->created,
                    'currency' => $data->currency,
                    'method' => $data->method,
                    'money' => $data->amount,
                    'source_type' => $data->source_type,
                    'status' => $this->getStatusPayoutHistory($data->status),
                    'type' => $data->type,
                ];
            }
            $payoutHistory['data'] = $dataResult;
        }
        if (isset($payoutHistory['data']) && count($payoutHistory['data']) == 0) {
            unset($payoutHistory['data']);
        }
        return $payoutHistory;
    }

    public function getPayoutRetrieveStore($stripeId)
    {
        try {
            $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
            $tripeInfo = $stripe->balance->retrieve(null, ['stripe_account' => $stripeId]);
            if ($tripeInfo) {
                $total = $this->getPayoutAmount($tripeInfo->available) + $this->getPayoutAmount($tripeInfo->pending);
            }
            return $total;
        } catch (\Exception $e) {
            return;
        }
    }

    private function getPayoutAmount($dataBalance)
    {
        $result = 0;
        foreach ($dataBalance as $item) {
            $result += $item->amount;
        }
        return $result;
    }

    /**
     *
     * @param  string|null $stripeId
     * @return collection
     */
    public function getPayoutHistoryCMS($request)
    {
        $tblStore = Store::getTableName();
        $tblPayout = Payout::getTableName();

        $columns = [
            "$tblStore.id as store_id",
            "$tblStore.name as store_name",
            "$tblPayout.id",
            "$tblPayout.arrival_date",
            "$tblPayout.created",
            "$tblPayout.currency",
            "$tblPayout.amount",
            "$tblPayout.method",
            "$tblPayout.source_type",
            "$tblPayout.stripe_bank_id as bank_id",
        ];
        return $this->payoutRepository->getAllPayout($request, $columns);
    }

    public function countStatusPayout($request)
    {
        $tblPayout = Payout::getTableName();
        $columns = ["$tblPayout.id"];
        if ($request && isset($request['status'])) {
            unset($request['status']);
        }
        $payoutsAll =  $this->payoutRepository->getAllPayout($request, $columns, false);
        $arrayStatus = [
            EnumStripe::STATUS_PAYOUT_HISTORY_PENDING => [
                'status' => EnumStripe::STATUS_PAYOUT_HISTORY_PENDING,
                'quantity' => 0
            ],
            EnumStripe::STATUS_PAYOUT_HISTORY_PAID => [
                'status' => EnumStripe::STATUS_PAYOUT_HISTORY_PAID,
                'quantity' => 0
            ],
            EnumStripe::STATUS_PAYOUT_HISTORY_FAILED => [
                'status' => EnumStripe::STATUS_PAYOUT_HISTORY_FAILED,
                'quantity' => 0
            ]
        ];

        foreach ($payoutsAll as $payout) {
            $arrayStatus[$payout->status]['quantity']++;
        }
        return array_values($arrayStatus);
    }

    public function insertPayout($event, $typeHook = null)
    {
        $dataPayout = $event->data->object;
        $accountId = $event->account;
        $hookType = $typeHook ? $typeHook : $event->type;

        $result = null;
        $now = Carbon::now();

        $dataInsert = [
            'stripe_payout_id' => $dataPayout->id,
            'currency' => $dataPayout->currency,
            'method' => $dataPayout->method,
            'amount' => $dataPayout->amount,
            'source_type' => $dataPayout->source_type,
            'status' => $dataPayout->status,
            'type' => $dataPayout->type,
            'stripe_account_id' => $accountId,
            'stripe_bank_id' => $dataPayout->destination,
            'automatic' => $dataPayout->automatic ? 1 : 0,
            'arrival_date' => $dataPayout->arrival_date,
            'hook_type' => $hookType,
            'created' => $dataPayout->created,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $result = $this->payoutRepository->insert($dataInsert);
        return $result;
    }

    public function updatePayout($event)
    {
        $dataPayout = $event->data->object;
        $hookType = $event->type;

        $payoutId = $dataPayout->id;
        $payout = $this->payoutRepository->findByPayoutId($payoutId);

        if ($payout) {
            $dataInsert = [
                'currency' => $dataPayout->currency,
                'method' => $dataPayout->method,
                'amount' => $dataPayout->amount,
                'source_type' => $dataPayout->source_type,
                'status' => $dataPayout->status,
                'type' => $dataPayout->type,
                'stripe_bank_id' => $dataPayout->destination,
                'automatic' => $dataPayout->automatic ? 1 : 0,
                'arrival_date' => $dataPayout->arrival_date,
                'hook_type' => $hookType,
                'created' => $dataPayout->created,
            ];

            $payout->update(['status' => $dataInsert]);
        } else {
            $payout = $this->insertPayout($event, $hookType);
        }
        return $payout;
    }

    public function updateStatusPayout($payoutId, $status)
    {
        $payout = $this->payoutRepository->findByPayoutId($payoutId);
        if ($payout) {
            $payout->update(['status' => $status]);
        }
        return $payout;
    }

    public function getPayoutHistoryDetailCMS($payoutId)
    {
        $tblStore = Store::getTableName();
        $tblPayout = Payout::getTableName();

        $columns = [
            "$tblStore.id as store_id",
            "$tblStore.name as store_name",
            "$tblPayout.id",
            "$tblPayout.arrival_date",
            "$tblPayout.created",
            "$tblPayout.currency",
            "$tblPayout.amount",
            "$tblPayout.stripe_bank_id",
        ];
        return $this->payoutRepository->getPayoutDetailCMS($payoutId, $columns);
    }

    public function getPayoutRetrieveCMS($storeId)
    {
        $total = 0;
        $store = $this->storeRepository->find($storeId);
        if ($store) {
            try {
                $total = $this->getPayoutRetrieveStore($store->acc_stripe_id);
            } catch (\Exception $e) {
            }
        }
        return $total;
    }

    public function deleteAccount($storeId)
    {
        $store = $this->storeRepository->find($storeId);
        if ($store) {
            $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
            return $stripe->accounts->delete(
                $store->acc_stripe_id,
                []
            );
        }
        return;
    }

    public function getStripeById($stripeId)
    {
        return $this->stripeRepository->find($stripeId);
    }

    public function retrieveStripeByAccountId($accountId, $isApprove = false)
    {
        try {
            $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
            return $stripe->accounts->retrieve(
                $accountId,
                []
            );
        } catch (\Exception $e) {
            if ($isApprove) {
                Log::channel('confirm_upgrade_account')->error("Approve error: Don't have account $accountId ");
            } else {
                Log::channel('confirm_upgrade_account')->warning("Cancel error: Don't have account $accountId. ");
            }
            return;
        }
    }
}
