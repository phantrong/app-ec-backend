<?php

namespace App\Services;

use App\Enums\EnumCustomer;
use App\Enums\EnumPasswordReset;
use App\Jobs\SendMailCustomer;
use App\Jobs\SendMailResetPassword;
use App\Jobs\SendMailSettingEmail;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Repositories\Customer\CustomerRepository;
use App\Repositories\Messenger\MessengerRepository;
use App\Repositories\PasswordReset\PasswordResetRepository;
use App\Repositories\Staff\StaffRepository;
use App\Repositories\Store\StoreRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerService
{
    private CustomerRepository $customerRepository;
    private PasswordResetRepository $passwordResetRepository;
    private StaffRepository $staffRepository;
    private MessengerRepository $messengerRepository;
    private StoreRepository $storeRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        PasswordResetRepository $passwordResetRepository,
        StaffRepository $staffRepository,
        MessengerRepository $messengerRepository,
        StoreRepository $storeRepository,
    ) {
        $this->customerRepository = $customerRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->staffRepository = $staffRepository;
        $this->messengerRepository = $messengerRepository;
        $this->storeRepository = $storeRepository;
    }

    public function store($data)
    {
        $customer = $this->getCustomerByEmail($data['email']);
        $data['password'] = Hash::make($data['password']);
        // $data['status'] = EnumCustomer::STATUS_CREATE;
        $data['status'] = EnumCustomer::STATUS_ACTIVE;
        if ($customer) {
            $this->customerRepository->update($customer->id, $data);
        } else {
            $customer = $this->customerRepository->create($data);
        }
        // $links = [
        //     'link_verify' => config('services.link_service_back') .
        //         'customers/verify/' . $data['verify_content'] . '/' . encrypt($customer->id),
        //     'link_home' => config('services.link_service_front')
        // ];
        // SendMailCustomer::dispatch($customer, $links);
        return $this->getCustomerByEmail($customer->email);
    }

    public function verifyCustomer($token, $customerId)
    {
        DB::beginTransaction();
        try {
            $customerId = decrypt($customerId);
            $customer = $this->customerRepository->find($customerId);
            if ($customer && $customer->status == EnumCustomer::STATUS_CREATE &&
                $customer->verify_content == $token
            ) {
                $customer->status = EnumCustomer::STATUS_ACTIVE;
                $customer->status_signup_store = null;
                $customer->save();
                // change password of staff same customer
                $this->staffRepository->updateStaffByEmail($customer->email, [
                    'password' => $customer->password
                ]);
                DB::commit();
                return true;
            }
            return false;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getCustomerByEmail($email)
    {
        return $this->customerRepository->getCustomerByEmail($email);
    }

    public function getProfileCustomer($customerId)
    {
        $customer = $this->customerRepository->getProfileCustomer($customerId);
        $customer->is_staff = !!$this->customerRepository->checkCustomerIsStaff($customerId);
        return $customer;
    }

    public function checkCustomerIsStaff($customerId)
    {
        return $this->customerRepository->checkCustomerIsStaff($customerId);
    }

    public function login($data)
    {
        $result = ['status' => JsonResponse::HTTP_UNAUTHORIZED];
        $customer = $this->getCustomerByEmail($data['email']);
        if ($customer) {
            if (Hash::check($data['password'], $customer->password) &&
                $customer->status == EnumCustomer::STATUS_ACTIVE
            ) {
                // $staff = $this->staffRepository->getStaffByEmail($data['email']);
                // $tokenStaff = $staff && $staff->is_owner ?
                //     $staff->createToken('authToken', [config('auth.token_staff')])->plainTextToken : null;
                $tokenCustomer = $customer->createToken('authToken', [config('auth.token_customer')])
                    ->plainTextToken;
                $result['status'] = JsonResponse::HTTP_OK;
                $result['token_customer'] = $tokenCustomer;
                // $result['token_staff'] = $tokenStaff;
            } elseif ($customer->status == EnumCustomer::STATUS_BLOCKED) {
                $result['status'] = JsonResponse::HTTP_FORBIDDEN;
            }
        }
        return $result;
    }

    public function sendMailResetPassword($customer)
    {
        $email = $customer->email;
        $token = Str::random();
        $emailHash = encrypt($email);
        $this->passwordResetRepository->createToken([
            'email' => $email,
            'type' => EnumPasswordReset::TYPE_CUSTOMER,
            'token' => $token,
            'created_at' => now()->format('Y-m-d H:i:s')
        ]);
        $links = [
            'link_reset' => config('services.link_service_front') . "customers/reset-password/$token/$emailHash",
            'link_home' => config('services.link_service_front')
        ];
        SendMailResetPassword::dispatch($customer, $links);
    }

    public function resSendMail($email)
    {
        $customer = $this->getCustomerByEmail($email);
        $token = Str::random();
        $emailHash = encrypt($email);
        $links = [
            'link_reset' => config('services.link_service_front') . "customers/reset-password/$token/$emailHash",
            'link_home' => config('services.link_service_front')
        ];
        $this->passwordResetRepository->updateToken($email, $token, EnumPasswordReset::TYPE_CUSTOMER);
        SendMailResetPassword::dispatch($customer, $links);
    }

    public function validateLinkResetPassword($token, $email): bool
    {
        try {
            $passwordReset = $this->passwordResetRepository->getEmailByToken($token, EnumPasswordReset::TYPE_CUSTOMER);
            return $passwordReset && decrypt($email) == $passwordReset->email;
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    public function resetPassword($password, $token)
    {
        $email = false;
        $password = Hash::make($password);
        $passwordReset = $this->passwordResetRepository->getEmailByToken($token, EnumPasswordReset::TYPE_CUSTOMER);
        if ($passwordReset) {
            $time = Carbon::parse($passwordReset->created_at)->addSeconds(config('auth.password_timeout'));
            if (!now()->gt($time)) {
                $this->customerRepository->resetPassword($passwordReset->email, $password);
                $email = $passwordReset->email;
            }
            $this->passwordResetRepository->deleteByEmail($passwordReset->email, EnumPasswordReset::TYPE_CUSTOMER);
        }
        return $email;
    }

    // update profile, address, email
    public function updateCustomer($customerId, $data)
    {
        return $this->customerRepository->update($customerId, $data);
    }

    public function sendMailSettingEmail($customer, $newEmail)
    {
        $newEmailHash = encrypt($newEmail);
        $oldEmailHash = encrypt($customer->email);
        $token = Str::random();
        $this->customerRepository->update($customer->id, ['verify_content' => $token]);
        $link = config('services.link_service_back') . "customers/change-email/$token/$oldEmailHash/$newEmailHash";
        SendMailSettingEmail::dispatch($customer, $newEmail, $link);
    }

    public function validateLinkSettingEmail($token, $emailHash): bool
    {
        $email = decrypt($emailHash);
        $customer = $this->getCustomerByEmail($email);
        $customerToken = $customer->verify_content ?? null;
        return $customerToken == $token;
    }

    public function changeEmail($token, $oldEmail, $newEmail)
    {
        if ($this->validateLinkSettingEmail($token, $oldEmail)) {
            $oldEmail = decrypt($oldEmail);
            $newEmail = decrypt($newEmail);
            return $this->customerRepository->changEmail($oldEmail, $newEmail);
        }
        return false;
    }

    public function updateCustomerByEmail($email, array $data)
    {
        return $this->customerRepository->updateCustomerByEmail($email, $data);
    }

    /**
     * Get customer list in CMS.
     *
     * @param array $condition
     * @return LengthAwarePaginator
     */
    public function getCustomerListCMS(array $condition)
    {
        $columns = [
            'id',
            'name',
            'email',
            'phone',
            'address',
            'created_at',
        ];

        return $this->customerRepository->getCustomerList($condition, $columns);
    }

    /**
     * Get customer detail in CMS.
     *
     * @param int $id
     * @return Builder|Model|object|array
     */
    public function getCustomerDetailCMS(int $id)
    {
        $columns = [
            'id',
            'avatar',
            'name',
            'phone',
            'email',
            'birthday',
            'gender',
            'address',
            'created_at',
        ];

        $customer = $this->customerRepository->getCustomerDetail($id, $columns);
        if (!$customer) {
            return responseArrError(JsonResponse::HTTP_NOT_FOUND, [config('errorCodes.customer.not_found')]);
        }

        return $customer;
    }

    public function getListUserChat($groupId)
    {
        $customers = $this->messengerRepository->getCustomerInGroup($groupId);
        $ids = [];
        foreach ($customers->toArray() as $customer) {
            $ids[] = $customer[0];
        }
        return $this->customerRepository->getUserInGroupChat($ids);
    }

    public function getCustomerDetailMessenger(int $id)
    {
        $tblCustomer = Customer::getTableName();

        $columns = [
            "$tblCustomer.id",
            'avatar',
            'name',
            'surname',
            'birthday',
            'gender'
        ];

        return $this->customerRepository->getCustomerDetail($id, $columns);
    }

    public function getArrayUserInformationInGroupChat(array $arrayIds)
    {
        return $this->customerRepository->getUserInGroupChat($arrayIds);
    }

    public function getArrayStoreInformationInGroupChat(array $arrayIds)
    {
        return $this->storeRepository->getStoreInGroupChat($arrayIds);
    }

    public function create(array $data)
    {
        return $this->customerRepository->create($data);
    }
}
