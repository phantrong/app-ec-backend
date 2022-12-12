<?php

namespace App\Http\Controllers;

use App\Enums\EnumCheckMail;
use App\Enums\EnumCustomer;
use App\Enums\EnumFile;
use App\Enums\EnumStore;
use App\Enums\EnumStripe;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\CustomerAddressRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\LoginCustomerRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SettingEmailRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Jobs\JobSendMailReceiveOrder;
use App\Jobs\SendMailStripeRejectAccount;
use App\Services\BankHistoryService;
use App\Services\CustomerAddressService;
use App\Http\Requests\UpgradeAccountCustomerRequest;
use App\Jobs\JobSendMailHaveRequestStoreForCms;
use App\Services\AdminService;
use App\Services\CustomerService;
use App\Services\MessengerService;
use App\Services\OrderService;
use App\Services\StaffService;
use App\Services\StoreService;
use App\Services\StripeService;
use App\Services\SubOrderService;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class CustomerController extends BaseController
{
    private CustomerService $customerService;
    private UploadService $uploadService;
    private StaffService $staffService;
    private SubOrderService $subOrderService;
    private OrderService $orderService;
    private CustomerAddressService $customerAddressService;
    private StoreService $storeService;
    private StripeService $stripeService;
    private BankHistoryService $bankHistoryService;
    private MessengerService $messengerService;
    private AdminService $adminService;

    public function __construct(
        CustomerService $customerService,
        UploadService $uploadService,
        StaffService $staffService,
        SubOrderService $subOrderService,
        OrderService $orderService,
        CustomerAddressService $customerAddressService,
        StoreService $storeService,
        StripeService $stripeService,
        BankHistoryService $bankHistoryService,
        MessengerService $messengerService,
        AdminService $adminService,
    ) {
        $this->customerService = $customerService;
        $this->uploadService = $uploadService;
        $this->staffService = $staffService;
        $this->subOrderService = $subOrderService;
        $this->orderService = $orderService;
        $this->customerAddressService = $customerAddressService;
        $this->storeService = $storeService;
        $this->stripeService = $stripeService;
        $this->bankHistoryService = $bankHistoryService;
        $this->messengerService = $messengerService;
        $this->adminService = $adminService;
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $dataCustomer = $request->only(
                'name',
                'email',
                'password',
                'gender',
                'birthday',
                'phone'
            );
            $this->customerService->store($dataCustomer);
            DB::commit();
            return $this->sendResponse([
                'message' => "Chúc mừng bạn đã đăng kí thàng công. Hãy đăng nhập để sử dụng hệ thống."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function verifyCustomer($token, $customerId)
    {
        try {
            $result = $this->customerService->verifyCustomer($token, $customerId);
            if ($result) {
                return redirect(config('services.link_service_front') . 'sign-up/success');
            }
            return redirect(config('services.link_service_front') . '404');
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function login(LoginCustomerRequest $request)
    {
        try {
            $result = $this->customerService->login($request->only('email', 'password'));
            $status = $result['status'];
            if ($status != JsonResponse::HTTP_OK) {
                return $this->sendResponse([
                    'message' => 'Thông tin tài khoản mật khẩu không chính xác.'
                ], $status, 'false');
            }
            return $this->sendResponse([
                'token_type' => 'Bearer',
                'token_customer' => $result['token_customer'],
                // 'token_staff' => $result['token_staff']
            ]);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function sendMailResetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $errorCode = "false";
            $email = $request->email;
            $customer = $this->customerService->getCustomerByEmail($email);
            if (!$customer) {
                $errorCode = config('errorCodes.account.customer_not_exists');
            } else {
                if ($customer->status == EnumCustomer::STATUS_BLOCKED) {
                    $errorCode = config('errorCodes.account.customer_block');
                } elseif ($customer->status == EnumCustomer::STATUS_CREATE) {
                    $errorCode = config('errorCodes.account.un_verify');
                } else {
                    $this->customerService->sendMailResetPassword($customer);
                }
            }
            $status = $errorCode == "false" ? JsonResponse::HTTP_OK : JsonResponse::HTTP_NOT_ACCEPTABLE;
            return $this->sendResponse([$errorCode], $status);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function validateLinkResetPassword(Request $request): JsonResponse
    {
        try {
            $result = $this->customerService->validateLinkResetPassword($request->token, $request->email);
            $data = $result ? "true" : "false";
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function reSendMail(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->customerService->resSendMail($request->email);
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function resetPassword(ChangePasswordRequest $request, $token): JsonResponse
    {
        DB::beginTransaction();
        try {
            $password = $request->password;
            $email = $this->customerService->resetPassword($password, $token);
            if ($email) {
                $this->staffService->synchronizeInfoStaff($email, [
                    'password' => Hash::make($password)
                ]);
                DB::commit();
                return $this->sendResponse(null);
            }
            return $this->sendResponse(null, JsonResponse::HTTP_UNPROCESSABLE_ENTITY, 'false');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            return $this->sendResponse($user);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $customer = Auth::user();
            $customerId = $customer->id;
            $data = $request->except('avatar');
            $this->customerService->updateCustomer($customerId, $data);
            DB::commit();
            return $this->sendResponse(['message' => "Cập nhật thông tin thành công."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateAddress(CustomerAddressRequest $request)
    {
        try {
            $customerId = $request->user()->id;
            $data = $request->all();
            $this->customerAddressService->updateCustomerAddress($customerId, $data);
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function checkEmail(Request $request)
    {
        try {
            $email = $request->email;
            $type = $request->type;
            $customer = $this->customerService->getCustomerByEmail($email);
            $errorCode = "false";
            switch ($type) {
                case EnumCheckMail::CHECK_MAIL_RESEND_PASSWORD_CUSTOMER:
                    if (!$customer) {
                        $errorCode = config('errorCodes.account.customer_not_exists');
                    }
                    if ($customer && $customer->status == EnumCustomer::STATUS_BLOCKED) {
                        $errorCode = config('errorCodes.account.customer_block');
                    }
                    break;
                case EnumCheckMail::CHECK_MAIL_REGISTER_CUSTOMER:
                    if ($customer && $customer->status == EnumCustomer::STATUS_ACTIVE) {
                        $errorCode = config('errorCodes.account.exists');
                    }
            }
            $status = $errorCode == "false" ? JsonResponse::HTTP_OK : JsonResponse::HTTP_NOT_ACCEPTABLE;
            return $this->sendResponse([$errorCode], $status);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getListOrder(Request $request): JsonResponse
    {
        try {
            $customerId = $request->user()->id;
            $data = $this->subOrderService->getListOrderByCustomer($request->all(), $customerId);
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getDetailOrder($orderId)
    {
        try {
            $items = $this->subOrderService->getDetailOrder($orderId);
            return $this->sendResponse($items);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getDetailOrderSiteUser($orderId)
    {
        try {
            $items = $this->subOrderService->getDetailOrderSiteUser($orderId);
            return $this->sendResponse($items);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function sendMailSettingEmail(SettingEmailRequest $request): JsonResponse
    {
        try {
            $customer = $request->user();
            $newEmail = $request->email;
            $errorCode = "false";
            $customerNew = $this->customerService->getCustomerByEmail($newEmail);
            if ($customerNew) {
                $errorCode = config('errorCodes.account.exists');
            } elseif (Hash::check($request->password, $customer->password)) {
                $this->customerService->sendMailSettingEmail($customer, $newEmail);
            } else {
                $errorCode = config('errorCodes.password.not_valid');
            }
            $status = $errorCode == "false" ? JsonResponse::HTTP_OK : JsonResponse::HTTP_NOT_ACCEPTABLE;
            return $this->sendResponse([$errorCode], $status);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function validateLinkSettingEmail(Request $request): JsonResponse
    {
        try {
            $token = $request->token;
            $email = $request->email;
            $errorCode = "false";
            if (!$this->customerService->validateLinkSettingEmail($token, $email)) {
                $errorCode = config('errorCodes.link.not_valid');
            }
            $status = $errorCode == "false" ? JsonResponse::HTTP_OK : JsonResponse::HTTP_NOT_ACCEPTABLE;
            return $this->sendResponse([$errorCode], $status);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function changeEmail($token, $oldEmail, $newEmail)
    {
        DB::beginTransaction();
        try {
            if ($this->customerService->changeEmail($token, $oldEmail, $newEmail)) {
                $oldEmail = decrypt($oldEmail);
                $newEmail = decrypt($newEmail);
                $this->staffService->synchronizeInfoStaff($oldEmail, ['email' => $newEmail]);
                DB::commit();
                return redirect(config('services.link_service_front') . 'my-page/setting/email?success=1');
            }
            return $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function settingPassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $customer = $request->user();
            $errorCode = "false";
            if (Hash::check($request->old_password, $customer->password)) {
                $dataUpdate = ['password' => Hash::make($request->password)];
                $this->customerService->updateCustomer($customer->id, $dataUpdate);
                $customer->currentAccessToken()->delete();
            } else {
                $errorCode = config('errorCodes.password.not_valid');
            }
            return $errorCode == "false" ? $this->sendResponse([
                'message' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.'
            ]) :
                $this->sendResponse([
                    'message' => 'Mật khẩu hiện tại không chính xác.'
                ], JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function settingNotify(Request $request): JsonResponse
    {
        try {
            $customerId = $request->user()->id;
            $this->customerService->updateCustomer($customerId, $request->only('send_mail'));
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function upgradeAccount(UpgradeAccountCustomerRequest $request)
    {
        if ($this->customerService->checkCustomerIsStaff($request->user()->id)) {
            return $this->sendResponse(
                null,
                JsonResponse::HTTP_NOT_ACCEPTABLE
            );
        }

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
            $customer = $request->user();
            $dataStore = $request->store;
            $dataStore['customer_id'] = Auth::id();
            $store = $this->storeService->createStore($dataStore);
            $this->customerService->updateCustomer($customer->id, ['store_id' => $store->id]);
            $dataStripe = $request->stripe;
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
                'customer_email' => $customer->email,
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

    /**
     * Get customer list in CMS.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCustomerListCMS(Request $request)
    {
        try {
            $customers = $this->customerService->getCustomerListCMS($request->all());
            return $this->sendResponse($customers);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get customer detail in CMS.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getCustomerDetailCMS(int $id)
    {
        try {
            $customer = $this->customerService->getCustomerDetailCMS($id);
            if (isset($customer['errorCode'])) {
                return $this->sendResponse($customer['errorCode'], $customer['status']);
            }
            return $this->sendResponse($customer);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * confirm receive order
     *
     * @param int $orderId
     * @return JsonResponse
     */
    public function confirmOrder(int $orderId): JsonResponse
    {
        DB::beginTransaction();
        try {
            $subOrder = $this->subOrderService->getDetailOrderSiteUser($orderId);
            $result = $this->subOrderService->confirmOrder($orderId);
            $this->orderService->updateSuccessOrder($subOrder['order_id']);
            if ($result) {
                // $customer = Auth::user();
                // if ($customer->send_mail) {
                //     JobSendMailReceiveOrder::dispatch($customer->email, $subOrder->toArray());
                // }
            }
            DB::commit();
            return $this->sendResponse([
                'message' => 'Cập nhật trạng thái đơn hàng thành công.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function getListUserChat($groupId): JsonResponse
    {
        try {
            $customers = $this->customerService->getListUserChat($groupId);
            return $this->sendResponse($customers);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getUserInformation(Request $request): JsonResponse
    {
        try {
            if ($request->id) {
                $customer = $this->customerService->getCustomerDetailMessenger($request->id);
                if ($customer) {
                    return $this->sendResponse($customer);
                }
                return $this->sendResponse(
                    null,
                    JsonResponse::HTTP_NOT_ACCEPTABLE,
                    [config('errorCodes.customer.not_found')]
                );
            }
            return $this->sendResponse($request->user());
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
}
