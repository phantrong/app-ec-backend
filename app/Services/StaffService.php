<?php

namespace App\Services;

use App\Enums\EnumCustomer;
use App\Enums\EnumPasswordReset;
use App\Enums\EnumStaff;
use App\Jobs\SendMailCreateStaffSuccess;
use App\Jobs\SendMailResetPassword;
use App\Jobs\SendMailSettingEmail;
use App\Jobs\SendMailSignUpStore;
use App\Models\Staff;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\CalendarStaff\CalendarStaffRepository;
use App\Repositories\Customer\CustomerRepository;
use App\Repositories\LiveStream\LiveStreamRepository;
use App\Repositories\PasswordReset\PasswordResetRepository;
use App\Repositories\Staff\StaffRepository;
use App\Repositories\StoreMailVerification\StoreMailVerificationRepository;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StaffService
{
    private $staffRepository;
    private PasswordResetRepository $passwordResetRepository;
    private LiveStreamRepository $livestreamRepository;
    private BookingRepository $bookingRepository;
    private CalendarStaffRepository $calendarStaffRepository;
    private StoreMailVerificationRepository $storeMailVerificationRepository;
    private CustomerRepository $customerRepository;

    public function __construct(
        StaffRepository $staffRepository,
        PasswordResetRepository $passwordResetRepository,
        BookingRepository $bookingRepository,
        LiveStreamRepository $liveStreamRepository,
        CalendarStaffRepository $calendarStaffRepository,
        StoreMailVerificationRepository $storeMailVerificationRepository,
        CustomerRepository $customerRepository,
    ) {
        $this->staffRepository = $staffRepository;
        $this->staffRepository = $staffRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->bookingRepository = $bookingRepository;
        $this->livestreamRepository = $liveStreamRepository;
        $this->calendarStaffRepository = $calendarStaffRepository;
        $this->customerRepository = $customerRepository;
        $this->storeMailVerificationRepository = $storeMailVerificationRepository;
    }

    public function getListStaff(array $condition)
    {
        $tblStaff = Staff::getTableName();
        $columns = [
            "$tblStaff.id",
            "$tblStaff.name",
            "$tblStaff.phone",
            "$tblStaff.gender",
            "$tblStaff.address",
            "$tblStaff.email",
            "$tblStaff.status",
        ];
        return $this->staffRepository->getListStaff($condition, $columns);
    }

    public function countStatusListStaff(array $condition)
    {
        $tblStaff = Staff::getTableName();
        $columns = ["$tblStaff.status"];
        if ($condition && isset($condition['status'])) {
            unset($condition['status']);
        }
        $staffAll = $this->staffRepository->getListStaff($condition, $columns, false);
        $arrayStatus = [
            EnumStaff::STATUS_ACCESS => [
                'status' => EnumStaff::STATUS_ACCESS,
                'quantity' => 0
            ],
            EnumStaff::STATUS_BLOCKED => [
                'status' => EnumStaff::STATUS_BLOCKED,
                'quantity' => 0
            ],
        ];
        foreach ($staffAll as $staff) {
            $arrayStatus[$staff->status]['quantity']++;
        }
        return array_values($arrayStatus);
    }

    public function getListActiveStaff()
    {
        $condition = [
            'store_id' => Auth::user()->store_id,
        ];

        if (!Auth::user()->is_owner) {
            $condition['staff_id'] = Auth::user()->id;
        }

        $tblStaff = Staff::getTableName();
        $columns = [
            "$tblStaff.id",
            "$tblStaff.name",
        ];

        return $this->staffRepository->getListActiveStaff($condition, $columns);
    }

    public function getStaffByEmail($email)
    {
        return $this->staffRepository->getStaffByEmail($email);
    }

    public function createStaff(array $input): bool
    {
        // Create new staff
        $characterSpecial = '!@#$%^&*~`';
        $numberRand = random_int(0, 9);
        $password = Str::random(4) . $characterSpecial[$numberRand] . $numberRand . Str::random(4);
        $input['password'] = $password;
        $input['store_id'] = Auth::user()->store_id;
        $staff = $this->staffRepository->create($input);
        if (!$staff) {
            return false;
        }
        $link = config('services.link_service_front_shop') . 'login';
        // Send mail of creating staff successful
        $sendMailInput = Arr::only($input, [
            'email',
            'name',
            'password'
        ]);
        $sendMailInput['link'] = $link;
        SendMailCreateStaffSuccess::dispatch($sendMailInput);

        return true;
    }

    public function updateStaff(int $id, array $input)
    {
        return $this->staffRepository->update($id, $input);
    }

    public function deleteStaff(int $id)
    {
        $result = $this->staffRepository->deleteStaff($id);
        if (!$result) {
            throw new \Exception('Delete failure');
        }

        return true;
    }

    public function synchronizeInfoStaff($email, array $data)
    {
        return $this->staffRepository->updateStaffByEmail($email, $data);
    }

    public function resetPassword($passwordHash, $token)
    {
        $email = false;
        $passwordReset = $this->passwordResetRepository->getEmailByToken($token, EnumPasswordReset::TYPE_STAFF);
        if ($passwordReset) {
            $time = Carbon::parse($passwordReset->created_at)->addSeconds(config('auth.password_timeout'));
            if (!now()->gt($time)) {
                $this->staffRepository->resetPassword($passwordReset->email, $passwordHash);
                $email = $passwordReset->email;
            }
            $this->passwordResetRepository->deleteByEmail($passwordReset->email, EnumPasswordReset::TYPE_STAFF);
        }
        return $email;
    }

    public function createAccountShop(object $customer)
    {
        $staff = new Staff();
        $staff->preventAttrSet = false;
        $staff->name = $customer->surname . $customer->name;
        $staff->email = $customer->email;
        $staff->password = $customer->password;
        $staff->store_id = $customer->store_id;
        $staff->phone = $customer->phone;
        $staff->gender = $customer->gender;
        $staff->address = $customer->address->province_name . ' ' .
            $customer->address->place . ' ' .
            $customer->address->city . ' ' .
            $customer->address->home_address;
        $staff->is_owner = EnumStaff::IS_OWNER;
        $staff->save();
        return;
    }

    public function sendMailSettingEmail($staff, $newEmail)
    {
        $newEmailHash = encrypt($newEmail);
        $oldEmailHash = encrypt($staff->email);
        $token = Str::random();
        $this->staffRepository->update($staff->id, ['verify_content' => $token]);
        $link = config('services.link_service_back') . "staff/change-email/$token/$oldEmailHash/$newEmailHash";
        SendMailSettingEmail::dispatch($staff, $newEmail, $link);
    }

    public function changeEmail($token, $oldEmail, $newEmail)
    {
        if ($this->validateLinkSettingEmail($token, $oldEmail)) {
            $oldEmail = decrypt($oldEmail);
            $newEmail = decrypt($newEmail);
            return $this->staffRepository->changeEmail($oldEmail, $newEmail);
        }
        return false;
    }

    public function validateLinkSettingEmail($token, $emailHash): bool
    {
        $email = decrypt($emailHash);
        $staff = $this->getStaffByEmail($email);
        $staffToken = $staff->verify_content ?? null;
        return $staffToken == $token;
    }

    public function sendMailResetPassword($email)
    {
        $staff = $this->getStaffByEmail($email);
        $token = Str::random();
        $emailHash = encrypt($email);
        $this->passwordResetRepository->createToken([
            'email' => $email,
            'type' => EnumPasswordReset::TYPE_STAFF,
            'token' => $token,
            'created_at' => now()->format('Y-m-d H:i:s')
        ]);
        $links = [
            'link_reset' => config('services.link_service_front_shop') . "staff/reset-password/$token/$emailHash",
            'link_home' => config('services.link_service_front_shop')
        ];
        SendMailResetPassword::dispatch($staff, $links);
    }

    public function reSendMail($email)
    {
        $staff = $this->getStaffByEmail($email);
        $token = Str::random();
        $emailHash = encrypt($email);
        $links = [
            'link_reset' => config('services.link_service_front_shop') . "staff/reset-password/$token/$emailHash",
            'link_home' => config('services.link_service_front_shop')
        ];
        $this->passwordResetRepository->updateToken($email, $token, EnumPasswordReset::TYPE_STAFF);
        SendMailResetPassword::dispatch($staff, $links);
    }

    public function validateLinkResetPassword($token, $email): bool
    {
        try {
            $passwordReset = $this->passwordResetRepository->getEmailByToken($token, EnumPasswordReset::TYPE_STAFF);
            return $passwordReset && decrypt($email) == $passwordReset->email;
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    // check staff has calendar booking or livestream
    public function checkStaffActive($staffId)
    {
        return $this->bookingRepository->checkStaffIsCallVideo($staffId)
            || $this->calendarStaffRepository->checkStaffHasCalendar($staffId)
            || $this->livestreamRepository->checkStaffIsLivestream($staffId)
            || $this->livestreamRepository->checkCalendarLivestream($staffId);
    }

    public function sendMailSignUp($email)
    {
        $token = Str::random();
        $email = strtolower($email);
        $emailHash = encrypt($email);
        $this->storeMailVerificationRepository->createOrUpdate($email, $token);
        $customer = $this->customerRepository->getCustomerByEmail($email);
        $linkSignUp = config('services.link_service_front_shop') . "sign-up/$token/$emailHash";
        SendMailSignUpStore::dispatch(
            $linkSignUp,
            $email,
            $customer && $customer->status_signup_store == EnumCustomer::STATUS_SIGNUP_FAILED
        );
    }

    public function validateLinkSignUp($token, $email): string
    {
        try {
            $emailVerification = $this->storeMailVerificationRepository->getEmailByToken($token);
            if ($emailVerification && decrypt($email) == $emailVerification->email) {
                return $emailVerification->email;
            }
            return false;
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }
}
