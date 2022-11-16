<?php

namespace App\Repositories\Customer;

use App\Enums\EnumCustomer;
use App\Enums\EnumStaff;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Staff;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    const PER_PAGE_IN_CMS = 10;

    public function getModel(): string
    {
        return Customer::class;
    }

    /**
     * Get customer list.
     *
     * @param array $condition
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getCustomerList(array $condition, $columns = ['*'])
    {
        $tblCustomer = Customer::getTableName();

        $perPage = Arr::get($condition, 'per_page', self::PER_PAGE_IN_CMS);
        $page = Arr::get($condition, 'page', 1);

        return $this->model
            ->where("$tblCustomer.status", EnumCustomer::STATUS_ACTIVE)
            ->when(isset($condition['name']), function ($query) use ($condition) {
                if ($condition['name'][0] == '%') {
                    $condition['name'] = "/" . $condition['name'];
                }
                $query->where(function ($q) use ($condition) {
                    $q->where('name', 'LIKE', "%{$condition['name']}%")
                        ->orWhere('surname', 'LIKE', "%{$condition['name']}%");
                });
            })
            ->when(isset($condition['start_date']), function ($query) use ($tblCustomer, $condition) {
                return $query->whereDate(
                    "$tblCustomer.created_at",
                    '>=',
                    date('Y-m-d', strtotime($condition['start_date']))
                );
            })
            ->when(isset($condition['end_date']), function ($query) use ($tblCustomer, $condition) {
                return $query->whereDate(
                    "$tblCustomer.created_at",
                    '<=',
                    date('Y-m-d', strtotime($condition['end_date']))
                );
            })
            ->orderByDesc("$tblCustomer.created_at")
            ->paginate($perPage, $columns, 'page', $page);
    }

    /**
     * Get customer detail by id.
     *
     * @param int $id
     * @param array $columns
     * @return Builder|Model|object
     */
    public function getCustomerDetail(int $id, $columns = ['*'])
    {
        $tblCustomer = Customer::getTableName();
        $tblCustomerAddress = CustomerAddress::getTableName();

        return $this->model
            ->join($tblCustomerAddress, "$tblCustomer.id", '=', 'customer_id')
            ->whereNull("$tblCustomerAddress.deleted_at")
            ->where("$tblCustomer.id", $id)
            ->first($columns);
    }

    public function getCustomerByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function resetPassword($email, $password)
    {
        return $this->model
            ->where('email', $email)
            ->update(['password' => $password]);
    }

    public function changEmail($oldEmail, $newEmail)
    {
        return $this->model
            ->where('email', $oldEmail)
            ->update(['email' => $newEmail]);
    }

    public function getProfileCustomer($customerId)
    {
        return $this->model
            ->where('id', $customerId)
            ->with([
                'address',
                'store',
                'store.bankHistoryCurrent',
                'stripe'
            ])
            ->first();
    }

    public function updateCustomerByEmail($email, array $data)
    {
        return $this->model
            ->where('email', $email)
            ->update($data);
    }

    public function getUserInGroupChat($userId)
    {
        return $this->model
            ->select('id', 'avatar')
            ->selectRaw('id as userId, concat(surname,name) as name')
            ->whereIn('id', $userId)
            ->get();
    }

    public function getInfoCustomer($customerId, $fields)
    {
        return $this->model
            ->where('id', $customerId)
            ->first($fields);
    }

    public function checkCustomerIsStaff($customerId)
    {
        $tblCustomer = Customer::getTableName();
        $tblStaff = Staff::getTableName();
        return $this->model
            ->join($tblStaff, "$tblStaff.email", '=', "$tblCustomer.email")
            ->where("$tblCustomer.id", $customerId)
            ->whereNull("$tblCustomer.status_signup_store")
            ->exists();
    }
}
