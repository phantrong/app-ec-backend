<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\EnumCustomer;
use App\Enums\EnumMessage;
use App\Enums\EnumStaff;
use App\Enums\EnumStore;
use App\Enums\EnumStripe;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginStaffRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendMailResetPasswordRequest;
use App\Http\Requests\SendMailSignUpStoreRequest;
use App\Http\Requests\SignUpStoreRequest;
use App\Http\Requests\StaffRequest;
use App\Http\Requests\UpgradeAccountCustomerRequest;
use App\Jobs\JobSendMailHaveRequestStoreForCms;
use App\Jobs\SendMailStripeRejectAccount;
use App\Services\AdminService;
use App\Services\BankHistoryService;
use App\Services\CustomerAddressService;
use App\Services\CustomerService;
use App\Services\ProvinceService;
use App\Services\StaffService;
use App\Services\StoreService;
use App\Services\StripeService;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    private StaffService $staffService;
    private CustomerService $customerService;
    private StoreService $storeService;
    private UploadService $uploadService;
    private StripeService $stripeService;
    private BankHistoryService $bankHistoryService;
    private CustomerAddressService $customerAddressService;
    private ProvinceService $provinceService;
    private AdminService $adminService;

    public function __construct(
        StaffService $staffService,
        CustomerService $customerService,
        StoreService $storeService,
        UploadService $uploadService,
        StripeService $stripeService,
        BankHistoryService $bankHistoryService,
        CustomerAddressService $customerAddressService,
        ProvinceService $provinceService,
        AdminService $adminService,
    ) {
        $this->staffService = $staffService;
        $this->customerService = $customerService;
        $this->storeService = $storeService;
        $this->uploadService = $uploadService;
        $this->stripeService = $stripeService;
        $this->bankHistoryService = $bankHistoryService;
        $this->customerAddressService = $customerAddressService;
        $this->provinceService = $provinceService;
        $this->adminService = $adminService;
    }

    public function login(LoginStaffRequest $request): JsonResponse
    {
        try {
            $staff = $this->staffService->getStaffByEmail($request->email);
            if (!$staff || !Hash::check($request->password, $staff->password)) {
                return $this->sendResponse(null, JsonResponse::HTTP_UNAUTHORIZED);
            }

            if ($staff->store->status == EnumStore::STATUS_BLOCKED || $staff->status == EnumStaff::STATUS_BLOCKED) {
                return $this->sendResponse(null, JsonResponse::HTTP_FORBIDDEN);
            }
            $customer = $this->customerService->getCustomerByEmail($request->email);
            $tokenCustomer = $customer && $customer->store_id && !$customer->status_signup_store ?
                $customer->createToken('authToken', [config('auth.token_customer')])->plainTextToken : null;
            $tokenStaff = $staff->createToken('authToken', [config('auth.token_staff')])->plainTextToken;
            $data = [
                'token_type' => 'Bearer',
                'token_staff' => $tokenStaff,
                'token_customer' => $tokenCustomer
            ];

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function logout()
    {
        try {
            auth('sanctum')->user()->currentAccessToken()->delete();
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get list of staff.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getListStaff(Request $request): JsonResponse
    {
        try {
            $staff = $this->staffService->getListStaff($request->all());
            return $this->sendResponse($staff);
        } catch (\Exception $exception) {
            return $this->sendError($exception);
        }
    }

    /**
     * count list staff by status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function countStatusListStaff(Request $request): JsonResponse
    {
        try {
            $status = $this->staffService->countStatusListStaff($request->all());
            return $this->sendResponse($status);
        } catch (\Exception $exception) {
            return $this->sendError($exception);
        }
    }

    /**
     * Get list of active staff.
     *
     * @return JsonResponse
     */
    public function getListActiveStaff(): JsonResponse
    {
        try {
            $staff = $this->staffService->getListActiveStaff();
            return $this->sendResponseData($staff);
        } catch (\Exception $exception) {
            return $this->sendError($exception);
        }
    }

    public function getInfomation(Request $request): JsonResponse
    {
        try {
            $staff = [
                'id' => $request->user()->id,
                'email' => $request->user()->email,
                'name' => $request->user()->name,
                'status' => $request->user()->status,
                'is_owner' => $request->user()->is_owner,
                'store_id' => $request->user()->store_id,
                'avatar' => $request->user()->store->avatar,
                'name_store' => $request->user()->store->name
            ];

            return $this->sendResponse($staff);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Create staff.
     *
     * @param StaffRequest $request
     * @return JsonResponse
     */
    public function createStaff(StaffRequest $request): JsonResponse
    {
        try {
            $result = $this->staffService->createStaff($request->all());
            $response = $this->sendResponse(null, JsonResponse::HTTP_OK, $result);
        } catch (\Exception $e) {
            $response = $this->sendError($e);
        }

        return $response;
    }

    /**
     * Update staff
     *
     * @param int $id
     * @param StaffRequest $request
     * @return JsonResponse
     */
    public function updateStaff(int $id, StaffRequest $request): JsonResponse
    {
        try {
            if ($id == Auth::id() && $request->status == EnumStaff::STATUS_BLOCKED) {
                return $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
            }
            if ($this->staffService->checkStaffActive($id) && $request->status == EnumStaff::STATUS_BLOCKED) {
                $errorCode = config('errorCodes.calendar_staff.available');
                return $this->sendResponse([$errorCode], JsonResponse::HTTP_NOT_ACCEPTABLE);
            }
            $result = $this->staffService->updateStaff($id, $request->all());
            $response = $this->sendResponse(null, JsonResponse::HTTP_OK, $result);
        } catch (\Exception $e) {
            $response = $this->sendError($e);
        }

        return $response;
    }

    /**
     * Delete staff.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteStaff(int $id): JsonResponse
    {
        try {
            if ($this->staffService->checkStaffActive($id)) {
                $errorCode = config('errorCodes.calendar_staff.available');
                return $this->sendResponse([$errorCode], JsonResponse::HTTP_NOT_ACCEPTABLE);
            }
            $this->staffService->deleteStaff($id);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function sendMailResetPassword(SendMailResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->staffService->sendMailResetPassword($request->email);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function resetPassword(ChangePasswordRequest $request, $token)
    {
        DB::beginTransaction();
        try {
            $passwordHash = Hash::make($request->password);
            $email = $this->staffService->resetPassword($passwordHash, $token);
            if ($email) {
                $this->customerService->updateCustomerByEmail($email, [
                    'password' => $passwordHash
                ]);
                DB::commit();
                return $this->sendResponse();
            }
            return $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function changeEmail($token, $oldEmail, $newEmail)
    {
        DB::beginTransaction();
        try {
            if ($this->staffService->changeEmail($token, $oldEmail, $newEmail)) {
                $oldEmail = decrypt($oldEmail);
                $newEmail = decrypt($newEmail);
                $this->customerService->updateCustomerByEmail($oldEmail, ['email' => $newEmail]);
                DB::commit();
                return redirect(config('services.link_service_front_shop') . 'setting/email?success=1');
            }
            return redirect(config('services.link_service_front_shop') . '404');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function reSendMail(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->staffService->reSendMail($request->email);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function validateLinkResetPassword(Request $request): JsonResponse
    {
        try {
            $data = $this->staffService->validateLinkResetPassword($request->token, $request->email);
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function checkEmail(Request $request)
    {
        try {
            $email = $request->email;
            $staff = $this->staffService->getStaffByEmail($email);
            $errorCode = false;
            if ($staff) {
                if ($staff->status == EnumStaff::STATUS_BLOCKED) {
                    $errorCode = config('errorCodes.account.customer_block');
                }
            } else {
                $errorCode = config('errorCodes.account.customer_not_exists');
            }
            return !$errorCode ? $this->sendResponse() :
                $this->sendResponse([$errorCode], JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getGroupChatInformation(Request $request): JsonResponse
    {
        try {
            if ($request->user) {
                $users = $this->customerService->getArrayUserInformationInGroupChat($request->user);
            }
            if ($request->store) {
                $stores = $this->customerService->getArrayStoreInformationInGroupChat($request->store);
            }
            if (!$users && !$stores) {
                return $this->sendResponse(
                    null,
                    JsonResponse::HTTP_NOT_ACCEPTABLE,
                    [config('errorCodes.customer.not_found')]
                );
            }
            $all = $stores->merge($users);
            return $this->sendResponse($all->all());
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function sendMailSignUp(SendMailSignUpStoreRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomerByEmail($request->email);
            $store = null;
            if ($customer) {
                $store = $customer->store;
            }
            $arrayStatusWait = [EnumStore::STATUS_WAITING_STRIPE, EnumStore::STATUS_NEW];
            if ($store && in_array($store->status, $arrayStatusWait)) {
                return $this->sendResponse(
                    [config('errorCodes.account.processing')],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $this->staffService->sendMailSignUp($request->email);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function validateLinkSignUp(Request $request): JsonResponse
    {
        try {
            $data = $this->staffService->validateLinkSignUp($request->token, $request->email);
            if ($data) {
                $customer = $this->customerService->getCustomerByEmail($data);
                $store = null;
                if ($customer) {
                    $store = $customer->store;
                }
                $arrayStatusFail = [EnumStore::STATUS_FAIL, EnumStore::STATUS_CANCEL];
                if ($customer && $customer->status_signup_store == EnumCustomer::STATUS_SIGNUP_FAILED
                    || ($store && in_array($store->status, $arrayStatusFail))
                ) {
                    $customerInfo = $this->customerService->getProfileCustomer($customer->id)->toArray();
                    if ($customerInfo['stripe']) {
                        $customerInfo['stripe'] = $customerInfo['stripe'][0];
                    } else {
                        $customerInfo['stripe'] = null;
                    }
                    return $this->sendResponse(['profile' => $customerInfo]);
                }
                return $this->sendResponse(['email' => $data]);
            }
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function signUpStore(UpgradeAccountCustomerRequest $request)
    {
        $clientIp = $request->getClientIp();
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
            $dataStore = $request->store;
            $dataStripe = $request->stripe;
            $customer = $this->customerService->getCustomerByEmail($request->email);
            if ($customer && $customer->status_signup_store) {
                $customer->status_signup_store = EnumCustomer::STATUS_SIGNUP_NEW;
                $customer->save();
                $dataCustomerAddress = [
                    'province_name' => $this->provinceService->getNameProvinceById($dataStripe['province_id']),
                    'postal_code' => $dataStripe['postal_code'],
                    'city' => $dataStripe['city'],
                    'place' => $dataStripe['place'],
                    'home_address' => isset($dataStripe['address']) ? $dataStripe['address'] : ''
                ];
                $this->customerAddressService->updateCustomerAddress($customer->id, $dataCustomerAddress);
            }
            if (!$customer) {
                $fakeCustomerInfo = [
                    'status' => EnumCustomer::STATUS_CREATE,
                    'email' => $request->email,
                    'surname' => $dataStripe['surname'],
                    'name' => $dataStripe['name'],
                    'surname_furigana' => $dataStripe['surname_furigana'],
                    'name_furigana' => $dataStripe['name_furigana'],
                    'password' => Hash::make(Str::random(8)),
                    'phone' => $dataStripe['phone'],
                    'gender' => $dataStripe['gender'],
                    'birthday' => $dataStripe['birthday'],
                    'status_signup_store' => EnumCustomer::STATUS_SIGNUP_NEW,
                ];
                $dataCustomerAddress = [
                    'province_name' => $this->provinceService->getNameProvinceById($dataStripe['province_id']),
                    'postal_code' => $dataStripe['postal_code'],
                    'city' => $dataStripe['city'],
                    'place' => $dataStripe['place'],
                    'home_address' => isset($dataStripe['address']) ? $dataStripe['address'] : ''
                ];
                $customer = $this->customerService->create($fakeCustomerInfo);
                $dataCustomerAddress['customer_id'] = $customer->id;
                $this->customerAddressService->createCustomerAddress($dataCustomerAddress);
            }
            $dataStore['customer_id'] = $customer->id;
            $store = $this->storeService->createStore($dataStore);
            $this->customerService->updateCustomer($customer->id, ['store_id' => $store->id]);
            $dataStripe['customer_id'] = $customer->id;
            $imageFirst = $request->image_card_first;
            $imageSecond = $request->image_card_second;
            if ($request->stripe_id) {
                $stripe = $this->stripeService->getStripeById($request->stripe_id);
                if (!$imageFirst) {
                    $imageCardFirst = $stripe->storage_image_card_first;
                    $imageCardFirstS3 = $stripe->image_card_first;
                } else {
                    $imageCardFirst = $this->uploadService->uploadFileStorage($imageFirst);
                    $imageCardFirstS3 = $this->uploadService->uploadFile(
                        $imageFirst,
                        null,
                        config('filesystems.folder_image_stripe_s3')
                    )[0];
                }
                if (!$imageSecond) {
                    $imageCardSecond = $stripe->storage_image_card_first;
                    $imageCardSecondS3 = $stripe->image_card_second;
                } else {
                    $imageCardSecond = $this->uploadService->uploadFileStorage($imageSecond);
                    $imageCardSecondS3 = $this->uploadService->uploadFile(
                        $imageSecond,
                        null,
                        config('filesystems.folder_image_stripe_s3')
                    )[0];
                }
            } else {
                $imageCardFirst = $this->uploadService->uploadFileStorage($imageFirst);
                $imageCardFirstS3 = $this->uploadService->uploadFile(
                    $imageFirst,
                    null,
                    config('filesystems.folder_image_stripe_s3')
                )[0];
                $imageCardSecond = $this->uploadService->uploadFileStorage($imageSecond);
                $imageCardSecondS3 = $this->uploadService->uploadFile(
                    $imageSecond,
                    null,
                    config('filesystems.folder_image_stripe_s3')
                )[0];
            }
            $dataStripe['image_card_first'] = $imageCardFirstS3;
            $dataStripe['image_card_second'] =  $imageCardSecondS3;
            $dataStripe['image_front_id'] = $this->stripeService->uploadImageCard($imageCardFirst);
            $dataStripe['image_back_id'] = $this->stripeService->uploadImageCard($imageCardSecond);
            $dataStripe['storage_image_card_first'] = $imageCardFirst;
            $dataStripe['storage_image_card_second'] = $imageCardSecond;
            $addressKana = $request->address_kana;
            $dataStripe['city_kana'] =  $addressKana['city'];
            $dataStripe['place_kana'] =  $addressKana['place'];
            $dataStripe['address_kana'] =  $addressKana['address'] ?? null;
            $stripe = $this->stripeService->createStripe($dataStripe);
            $dataBank = $request->bank;
            $bankAccount = $this->stripeService->createBankAccountToKen($dataBank);
            $account = $this->stripeService->createAccount($stripe, $clientIp);
            $externalAccount = $this->stripeService->createExternalAccount($account->id, $bankAccount->id);
            $dataBank['external_account_id'] = $externalAccount->id;
            $dataBank['store_id'] = $store->id;
            $bank = $this->bankHistoryService->createBankHistory($dataBank);
            $statusStore = $account->individual->verification->status === 'verified' ?
                EnumStore::STATUS_NEW : EnumStore::STATUS_WAITING_STRIPE;
            $store->update([
                'bank_history_id_current' => $bank->id,
                'acc_stripe_id' => $account->id,
                'status' => $statusStore
            ]);
            $stripe->update(['person_stripe_id' => $account->id]);
            $dataMail = [
                'email' => $this->adminService->getAllAdmin()->pluck('email')->toArray(),
                'customer_name' => $dataStripe['surname'] . $dataStripe['name'],
                'customer_email' => $request->email,
            ];
            JobSendMailHaveRequestStoreForCms::dispatch($dataMail);
            DB::commit();
            return $this->sendResponse();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('upgrade_account')->error($e);

            if ($e instanceof ApiErrorException) {
                $statusResponse = JsonResponse::HTTP_NOT_ACCEPTABLE;
                $error = $e->getMessage();

                if (strpos($error, EnumStripe::ERROR_PHONE_NUMBER)) {
                    $errorCode = config('errorCodes.stripe.errors.phone_number_valid');
                } elseif (strpos($error, EnumStripe::ERROR_ADDRESS)) {
                    $errorCode = config('errorCodes.stripe.errors.address');
                } elseif (strpos($error, EnumStripe::ERROR_BANK_NUMBER) ||
                    strpos($error, EnumStripe::ERROR_BRANCH_NUMBER)
                ) {
                    $errorCode = config('errorCodes.stripe.errors.bank_number');
                } else {
                    $errorCode = config('errorCodes.stripe.errors.stripe_error');
                }

                return $this->sendResponse([$errorCode], $statusResponse);
            }

            $response = [
                "success" => false,
                "messages" => 'System error',
            ];
            return response()->json($response, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
