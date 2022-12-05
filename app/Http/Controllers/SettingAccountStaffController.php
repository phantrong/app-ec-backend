<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\SettingEmailRequest;
use App\Services\CustomerService;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SettingAccountStaffController extends BaseController
{
    private StaffService $staffService;
    private CustomerService $customerService;

    public function __construct(StaffService $staffService, CustomerService $customerService)
    {
        $this->staffService = $staffService;
        $this->customerService = $customerService;
    }

    public function sendMailSettingEmail(SettingEmailRequest $request): JsonResponse
    {
        try {
            $staff = $request->user();
            $newEmail = $request->email;
            $errorCode = "false";
            if (Hash::check($request->password, $staff->password)) {
                $this->staffService->sendMailSettingEmail($staff, $newEmail);
            } else {
                $errorCode = config('errorCodes.password.not_valid');
            }
            return $errorCode == "false" ? $this->sendResponse() :
                $this->sendResponse([$errorCode], JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function settingPassword(ChangePasswordRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $staff = $request->user();
            $errorCode = "false";
            if (Hash::check($request->old_password, $staff->password)) {
                $password = $request->password;
                $this->staffService->updateStaff($staff->id, ['password' => $password]);
                $staff->currentAccessToken()->delete();
                DB::commit();
            } else {
                $errorCode = config('errorCodes.password.not_valid');
            }
            return $errorCode == "false" ? $this->sendResponse([
                'messages' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.'
            ]) :
                $this->sendResponse([
                    'messages' => 'Mật khẩu hiện tại không chính xác.'
                ], JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }
}
