<?php

namespace App\Http\Controllers;

use App\Enums\EnumCustomer;
use App\Enums\EnumStore;
use App\Enums\EnumSubOrder;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginAdminRequest;
use App\Http\Requests\RevenueOrderRequest;
use App\Http\Requests\SendMailPasswordResetAdmin;
use App\Services\AdminService;
use App\Services\CustomerService;
use App\Services\ManagerRevenueService;
use App\Services\StaffService;
use App\Services\StoreService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends BaseController
{
    private StripeService $stripeService;
    private StoreService $storeService;
    private CustomerService $customerService;
    private StaffService $staffService;
    private ManagerRevenueService $ManagerRevenueService;

    public function __construct(
        StripeService $stripeService,
        StoreService $storeService,
        CustomerService $customerService,
        StaffService $staffService,
        ManagerRevenueService $ManagerRevenueService,
        AdminService $adminService
    ) {
        $this->stripeService = $stripeService;
        $this->storeService = $storeService;
        $this->customerService = $customerService;
        $this->staffService = $staffService;
        $this->revenueService = $ManagerRevenueService;
        $this->adminService = $adminService;
    }

    public function login(LoginAdminRequest $request)
    {
        try {
            $admin = $this->adminService->getAdminByEmail($request->email);
            if ($admin && Hash::check($request->password, $admin->password)) {
                $token = $admin->createToken('authToken', [config('auth.token_admin')])->plainTextToken;
                return $this->sendResponse([
                    'token_type' => 'Bearer',
                    'token' => $token
                ]);
            }
            return $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getInfoAdmin()
    {
        try {
            return $this->sendResponse(Auth::user());
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function sendMailResetPassword(SendMailPasswordResetAdmin $request)
    {
        try {
            return $this->adminService->sendMailResetPassword($request->email);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function reSendMail(SendMailPasswordResetAdmin $request): JsonResponse
    {
        try {
            $this->adminService->resSendMail($request->email);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function resetPassword(ChangePasswordRequest $request, $token)
    {
        try {
            $result = $this->adminService->resetPassword($request->password, $token);
            return $result ? $this->sendResponse() : $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function validateLinkResetPassword(Request $request): JsonResponse
    {
        try {
            $result = $this->adminService->validateLinkResetPassword($request->token, $request->email);
            return $result ? $this->sendResponse() : $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $admin = $request->user();
            if (!Hash::check($request->old_password, $admin->password)) {
                $errorCode = config('errorCodes.password.not_valid');
                return $this->sendResponse([$errorCode], JsonResponse::HTTP_NOT_ACCEPTABLE);
            }
            $password = Hash::make($request->password);
            $this->adminService->updateByEmail($admin->email, [
                'password' => $password
            ]);
            $admin->currentAccessToken()->delete();
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getListAccountUpgrade(Request $request)
    {
        try {
            $listRequest = $this->stripeService->getListAccountUpgrade($request->status);
            return $this->sendResponse($listRequest);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function countStatusListAccount()
    {
        try {
            $status = $this->stripeService->countStatusListAccount(null);
            return $this->sendResponse($status);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function cancelRequestUpgrade(Request $request)
    {
        DB::beginTransaction();
        try {
            $storeId = $request->store_id;
            $customer = $this->customerService->getCustomerByEmail($request->email);
            $store = $customer->store;
            if ($this->stripeService->retrieveStripeByAccountId($store->acc_stripe_id) &&
                $store->status == EnumStore::STATUS_NEW
            ) {
                if ($customer && $customer->status_signup_store == EnumCustomer::STATUS_SIGNUP_NEW) {
                    $customer->status_signup_store = EnumCustomer::STATUS_SIGNUP_FAILED;
                    $customer->save();
                }
                $this->stripeService->deleteAccount($storeId);
                $profileCustomer = $this->customerService->getProfileCustomer($customer->id);
                if ($profileCustomer->stripe && $profileCustomer->stripe[0]) {
                    $infoCustomer = [
                        'name' => $profileCustomer->stripe[0]->surname . $profileCustomer->stripe[0]->name,
                    ];
                } else {
                    $infoCustomer = [
                        'name' => '',
                    ];
                }
                $this->adminService->sendMailCancelAccount(
                    $request->email,
                    $infoCustomer,
                    $customer && $customer->status_signup_store
                );
            }
            $this->storeService->updateStore($storeId, ['status' => EnumStore::STATUS_CANCEL]);
            DB::commit();
            return $this->sendResponse();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function approveRequestUpgrade(Request $request)
    {
        DB::beginTransaction();
        try {
            $fakePassword = '';
            $customer = $this->customerService->getCustomerByEmail($request->email);
            $store = $customer->store;
            if ($this->stripeService->retrieveStripeByAccountId($store->acc_stripe_id, true) &&
                $store->status == EnumStore::STATUS_NEW
            ) {
                if ($customer && $customer->status_signup_store) {
                    $fakePassword = Str::random(8);
                    $customer->status_signup_store = EnumCustomer::STATUS_SIGNUP_NEW;
                    $customer->password = Hash::make($fakePassword);
                    $customer->save();
                }
                $code = EnumStore::PREFIX_CODE . $store->id;
                $this->storeService->updateStore($customer->store_id, [
                    'status' => EnumStore::STATUS_CONFIRMED,
                    'commission' => EnumStore::COMMISSION_DEFAULT,
                    'code' => $code,
                    'date_applicable_commission' => now()->format('Y-m-d H:i:s'),
                    'date_approved' => now()->format('Y-m-d H:i:s')
                ]);
                $this->staffService->createAccountShop($customer);
                $profileCustomer = $this->customerService->getProfileCustomer($customer->id);
                if ($profileCustomer->stripe && $profileCustomer->stripe[0]) {
                    $infoCustomer = [
                        'name' => $profileCustomer->stripe[0]->surname . $profileCustomer->stripe[0]->name,
                        'email' => $customer->email
                    ];
                } else {
                    $infoCustomer = [
                        'name' => '',
                        'email' => $customer->email
                    ];
                }
                $this->adminService->sendMailApproveAccount($request->email, $infoCustomer, $fakePassword);
                DB::commit();
                return $this->sendResponse();
            }
            return $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function getRevenueOrderByStore(RevenueOrderRequest $request): JsonResponse
    {
        try {
            $type = $request->type ?? EnumSubOrder::UNIT_DAY;
            $isPostStartDate = $request->start_date;
            $isPostEndDate = $request->end_date;
            $dataRevenue = $this->revenueService->handleDateRevenue(
                $request->start_date,
                $request->end_date,
                $type,
                $isPostStartDate,
                $isPostEndDate
            );
            $startDate = $dataRevenue['start_date'];
            $endDate = $dataRevenue['end_date'];
            $revenue = $this->revenueService->getRevenueOrderByStore($startDate, $endDate, $type);
            return $this->sendResponse($revenue);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function exportRevenueOfStoreByOrder(RevenueOrderRequest $request)
    {
        try {
            $type = $request->type ?? EnumSubOrder::UNIT_DAY;
            $isPostStartDate = $request->start_date;
            $isPostEndDate = $request->end_date;
            $dataRevenue = $this->revenueService->handleDateRevenue(
                $request->start_date,
                $request->end_date,
                $type,
                $isPostStartDate,
                $isPostEndDate
            );
            $startDate = $dataRevenue['start_date'];
            $endDate = $dataRevenue['end_date'];
            return $this->revenueService->exportRevenueOrderByStore($startDate, $endDate, $type);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getRevenueOfStoreByProduct(Request $request): JsonResponse
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $products = $this->revenueService->getRevenueByProduct($startDate, $endDate);
            return $this->sendResponse($products);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function exportRevenueOfStoreByProduct(Request $request)
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            return $this->revenueService->exportRevenueProduct($startDate, $endDate);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function statisticRevenueOfStoreByAge(Request $request): JsonResponse
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $data = $this->revenueService->statisticOrderByAge($startDate, $endDate);
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function exportRevenueOfStoreByAge(Request $request)
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            return $this->revenueService->exportRevenueOfStoreByAge($startDate, $endDate);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function detailAccountUpgrade($stripeId)
    {
        try {
            $data = $this->stripeService->detailAccountUpgrade($stripeId);
            return $this->sendResponse($data);
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
