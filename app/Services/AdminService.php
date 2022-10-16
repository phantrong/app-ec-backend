<?php

namespace App\Services;

use App\Enums\EnumPasswordReset;
use App\Jobs\SendMailApproveAccount;
use App\Jobs\SendMailResetPassword;
use App\Mail\SendMailConfirmAccount;
use App\Repositories\Admin\AdminRepository;
use App\Repositories\PasswordReset\PasswordResetRepository;
use App\Repositories\StoreMailVerification\StoreMailVerificationRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminService
{
    public function __construct(
        AdminRepository $adminRepository,
        PasswordResetRepository $passwordResetRepository,
        StoreMailVerificationRepository $storeMailVerificationRepository
    ) {
        $this->adminRepository = $adminRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->storeMailVerificationRepository = $storeMailVerificationRepository;
    }

    public function getAdminByEmail($email)
    {
        return $this->adminRepository->getAdminByEmail($email);
    }

    public function sendMailResetPassword($email)
    {
        $admin = $this->getAdminByEmail($email);
        $token = Str::random();
        $emailHash = encrypt($email);
        $this->passwordResetRepository->createToken([
            'email' => $email,
            'type' => EnumPasswordReset::TYPE_ADMIN,
            'token' => $token,
            'created_at' => now()->format('Y-m-d H:i:s')
        ]);
        $links = [
            'link_reset' => config('services.link_service_front_cms') . "cms/reset-password/$token/$emailHash",
            'link_home' => config('services.link_service_front_cms')
        ];
        SendMailResetPassword::dispatch($admin, $links);
    }

    public function resSendMail($email)
    {
        $admin = $this->getAdminByEmail($email);
        $token = Str::random();
        $emailHash = encrypt($email);
        $links = [
            'link_reset' => config('services.link_service_front_cms') . "cms/reset-password/$token/$emailHash",
            'link_home' => config('services.link_service_front_cms')
        ];
        $this->passwordResetRepository->updateToken($email, $token, EnumPasswordReset::TYPE_ADMIN);
        SendMailResetPassword::dispatch($admin, $links);
    }

    public function validateLinkResetPassword($token, $email): bool
    {
        try {
            $passwordReset = $this->passwordResetRepository->getEmailByToken($token, EnumPasswordReset::TYPE_ADMIN);
            return $passwordReset && decrypt($email) == $passwordReset->email;
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    public function resetPassword($password, $token)
    {
        $result = false;
        $password = Hash::make($password);
        $passwordReset = $this->passwordResetRepository->getEmailByToken($token, EnumPasswordReset::TYPE_ADMIN);
        if ($passwordReset) {
            $time = Carbon::parse($passwordReset->created_at)->addSeconds(config('auth.password_timeout'));
            if (!now()->gt($time)) {
                $this->updateByEmail($passwordReset->email, [
                    'password' => $password
                ]);
            }
            $this->passwordResetRepository->deleteByEmail($passwordReset->email, EnumPasswordReset::TYPE_ADMIN);
            $result = true;
        }
        return $result;
    }

    public function updateByEmail($email, $data)
    {
        return $this->adminRepository->updateByEmail($email, $data);
    }

    public function sendMailApproveAccount($email, $customer, $fakePassword = '')
    {
        SendMailApproveAccount::dispatch($email, $customer, 'mail_template.approve_account', '', $fakePassword);
    }

    public function sendMailCancelAccount($email, $customer, $isSignUpStore = false)
    {
        $linkSignUp = config('services.link_service_front') . 'my-page/upgrade-shop';
        if ($isSignUpStore) {
            $token = Str::random();
            $emailHash = encrypt($email);
            $this->storeMailVerificationRepository->createOrUpdate($email, $token);
            $linkSignUp = config('services.link_service_front_shop') . "sign-up/$token/$emailHash";
        }
        SendMailApproveAccount::dispatch($email, $customer, 'mail_template.cancel_account', $linkSignUp);
    }

    public function getAllAdmin()
    {
        return $this->adminRepository->getAllAdmin();
    }
}
