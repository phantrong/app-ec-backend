<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\EnumFile;
use App\Enums\EnumInitType;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\UpdateBankRequest;
use App\Http\Requests\UpdateShopRequest;
use App\Jobs\UpdateInfoUserMongo;
use App\Services\BankHistoryService;
use App\Services\MessengerService;
use App\Services\StoreService;
use App\Services\StripeService;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreController extends BaseController
{
    private StoreService $storeService;
    private UploadService $uploadService;
    private StripeService $stripeService;
    private BankHistoryService $bankHistoryService;
    private MessengerService $messengerService;

    public function __construct(
        StoreService $storeService,
        UploadService $uploadService,
        StripeService $stripeService,
        BankHistoryService $bankHistoryService,
        MessengerService $messengerService
    ) {
        $this->storeService = $storeService;
        $this->uploadService = $uploadService;
        $this->stripeService = $stripeService;
        $this->bankHistoryService = $bankHistoryService;
        $this->messengerService = $messengerService;
    }

    public function getStore(Request $request): JsonResponse
    {
        try {
            $storeId = $request->user()->store_id;
            $store = $this->storeService->getStore($storeId);

            return $this->sendResponse($store);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateShop(UpdateShopRequest $request)
    {
        DB::beginTransaction();
        try {
            // update key name of request
            $stripeArray = $request->stripe;
            $stripeArray['surname'] = $stripeArray['first_name'];
            $stripeArray['name'] = $stripeArray['last_name'];
            $stripeArray['surname_furigana'] = $stripeArray['first_name_furigana'];
            $stripeArray['name_furigana'] = $stripeArray['last_name_furigana'];
            unset($stripeArray['first_name']);
            unset($stripeArray['last_name']);
            unset($stripeArray['first_name_furigana']);
            unset($stripeArray['last_name_furigana']);
            $request->merge(['stripe' =>  $stripeArray]);
            $storeId = $request->user()->store_id;
            $dataStore = $request->store;
            $dataStore['work_day'] = implode(',', $request->store['work_day']);
            $store = $this->storeService->getStore($storeId);
            if ($request->has('avatar')) {
                if ($request->avatar) {
                    $avatar = $this->uploadService->uploadFile($request->avatar, EnumFile::IMAGE_AVATAR);
                    $avatar = $avatar[0];
                } else {
                    $avatar = null;
                }
                $dataStore['avatar'] = $avatar;
            }
            if ($request->has('cover_image')) {
                if ($request->cover_image) {
                    $coverImage = $this->uploadService->uploadFile($request->cover_image, EnumFile::IMAGE_BACK_GROUND);
                    $coverImage = $coverImage[0];
                } else {
                    $coverImage = null;
                }
                $dataStore['cover_image'] = $coverImage;
            }
            $dataStripe = $request->stripe;
            $addressKana= $request->address_kana;
            $dataStripe['city_kana'] =  $addressKana['city'];
            $dataStripe['place_kana'] =  $addressKana['place'];
            $dataStripe['address_kana'] =  $addressKana['address'] ?? null;
            if ($request->image_card_first) {
                $dataStripe['storage_image_card_first'] = $this->uploadService->uploadFileStorage(
                    $request->image_card_first
                );
                $dataStripe['image_card_first'] = $this->uploadService->uploadFile(
                    $request->image_card_first,
                    null,
                    config('filesystems.folder_image_stripe_s3')
                )[0];
            }
            if ($request->image_card_second) {
                $dataStripe['storage_image_card_second'] = $this->uploadService->uploadFileStorage(
                    $request->image_card_second
                );
                $dataStripe['image_card_second'] = $imageCardSecondS3 = $this->uploadService->uploadFile(
                    $request->image_card_second,
                    null,
                    config('filesystems.folder_image_stripe_s3')
                )[0];
            }
            if ($dataStore) {
                $store->update($dataStore);
                $dataUpdateStoreMongo = [
                    'avatar' => $store->avatar,
                    'name' => $store->name,
                    'storeId' => $storeId
                ];
            }
            $this->messengerService->updateInfoShop($store->id, $dataUpdateStoreMongo);
            if ($dataStripe) {
                $this->stripeService->updateStripe($request->stripe_id, $dataStripe);
            }
            DB::commit();
            return $this->sendResponse();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function updateBankAccount(UpdateBankRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $store = $user->store;
            $bankHistoryCurrent = $store->bankHistoryCurrent;
            $stripe = $store->stripe;
            $accountId = $stripe->person_stripe_id;
            $dataBankHistory = $request->only(
                'bank_id',
                'branch_id',
                'customer_name',
                'type',
                'bank_number'
            );
            $dataBankHistory['store_id'] = $store->id;
            $bankAccount = $this->stripeService->createBankAccountToKen($dataBankHistory);
            $bankAccountExternal = $this->stripeService->createExternalAccountDefault($accountId, $bankAccount->id);
            $this->stripeService->deleteExternalAccount($accountId, $bankHistoryCurrent->external_account_id);
            $dataBankHistory['external_account_id'] = $bankAccountExternal->id;
            $bankHistoryNew = $this->bankHistoryService->createBankHistory($dataBankHistory);
            $store->update(['bank_history_id_current' => $bankHistoryNew->id]);
            DB::commit();
            return $this->sendResponse();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function getDetailBank()
    {
        try {
            $storeId = Auth::user()->store_id;
            $bank = $this->storeService->getDetailBank($storeId);
            return $this->sendResponse($bank);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
