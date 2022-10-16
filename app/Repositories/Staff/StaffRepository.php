<?php

namespace App\Repositories\Staff;

use App\Enums\EnumStaff;
use App\Enums\EnumStore;
use App\Models\Booking;
use App\Models\CalendarStaff;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Store;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffRepository extends BaseRepository implements StaffRepositoryInterface
{
    const PER_PAGE = 10;
    const PAGE_DEFAULT = 1;

    public function getModel()
    {
        return Staff::class;
    }

    /**
     * Get list of staff, relative query with name or email column.
     *
     * @param array $condition
     * @param array $columns
     * @return LengthAwarePaginator|Collection
     */
    public function getListStaff(array $condition, $columns = ['*'], $isPaginate = true)
    {
        $userId = Auth::id();
        $isOwner = EnumStaff::IS_OWNER;
        $perPage = $condition['per_page'] ?? self::PER_PAGE;
        $page = $condition['page'] ?? self::PAGE_DEFAULT;
        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();

        $query = $this->model->where("$tblStaff.store_id", Auth::user()->store_id)
            ->leftJoin($tblCustomer, "$tblCustomer.email", '=', "$tblStaff.email")
            ->withCount([
                    'calendar' => function ($query) {
                        return $query->whereDate('reception_date', '>=', now()->format('Y-m-d'));
                    },
                    'livestream' => function ($query) {
                        return $query->whereDate('start_time', '>=', now()->format('Y-m-d'));
                    }
                ]);
        if (isset($condition['status']) && $condition['status']) {
            $query = $query->where("$tblStaff.status", $condition['status']);
        }
        if (isset($condition['keyword'])) {
            $keywordValue = "%{$condition['keyword']}%";
            $query = $query->where(function ($q) use ($keywordValue, $tblStaff) {
                $q->where("$tblStaff.name", 'like', $keywordValue)
                    ->orWhere("$tblStaff.email", 'like', $keywordValue);
            });
        }

        $staffs = $query->addSelect(
            DB::raw("CASE WHEN $tblStaff.id = $userId OR is_owner = $isOwner THEN 1 ELSE 0 END AS is_owner")
        )
            ->addSelect("$tblCustomer.id as customer_id")
            ->orderBy("$tblStaff.status");
        if ($isPaginate) {
            return $staffs->paginate($perPage, $columns, 'page', $page);
        }
        return $staffs->get();
    }

    /**
     * Get list of active staff.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getListActiveStaff(array $condition, $columns = ['*'])
    {
        $tblStaff = $this->model->getTableName();
        $tblStore = Store::getTableName();

        return $this->model
            ->join($tblStore, "$tblStaff.store_id", '=', "$tblStore.id")
            ->when(isset($condition['store_id']), function ($query) use ($tblStaff, $condition) {
                $query->where("$tblStaff.store_id", $condition['store_id']);
            })
            ->when(isset($condition['staff_id']), function ($query) use ($tblStaff, $condition) {
                $query->where("$tblStaff.id", $condition['staff_id']);
            })
            ->where("$tblStore.status", EnumStore::STATUS_CONFIRMED)
            ->where("$tblStaff.status", EnumStaff::STATUS_ACCESS)
            ->get($columns);
    }

    public function getStaffByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get detail from booking.
     *
     * @param array $condition customer_id, booking_id, status list array
     * @param array $columns
     * @return Builder|Model|object
     */
    public function getDetailFromBooking(array $condition, $columns = ['*'])
    {
        $tblStaff = $this->model->getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblBooking = Booking::getTableName();
        $tblCustomer = Customer::getTableName();

        return $this->model->join($tblCalendarStaff, "$tblStaff.id", '=', "$tblCalendarStaff.staff_id")
            ->join($tblBooking, "$tblCalendarStaff.id", '=', "$tblBooking.calendar_staff_id")
            ->join($tblCustomer, "$tblBooking.customer_id", '=', "$tblCustomer.id")
            ->whereNull("$tblCalendarStaff.deleted_at")
            ->whereNull("$tblBooking.deleted_at")
            ->where("$tblBooking.customer_id", $condition['customer_id'])
            ->where("$tblBooking.id", $condition['booking_id'])
            ->whereIn("$tblBooking.status", $condition['status_list'])
            ->first($columns);
    }

    /**
     * Get owner list.
     *
     * @param array $storeIdArr
     * @param array $columns
     * @return Builder|Model|object
     */
    public function getOwnerList(array $storeIdArr, $columns = ['*'])
    {
        return $this->model
            ->whereIn('store_id', $storeIdArr)
            ->where('is_owner', EnumStaff::IS_OWNER)
            ->get($columns);
    }

    /**
     * Get owner detail.
     *
     * @param int $storeId
     * @param array $columns
     * @return Builder|Model|object
     */
    public function getOwnerDetail(int $storeId, $columns = ['*'])
    {
        return $this->model
            ->where('store_id', '=', $storeId)
            ->where('is_owner', EnumStaff::IS_OWNER)
            ->first($columns);
    }

    /**
     * Delete staff in same store by id.
     *
     * @param int $id
     * @return mixed
     */
    public function deleteStaff(int $id)
    {
        return $this->model->where('id', $id)
            ->where('store_id', Auth::user()->store_id)
            ->where('is_owner', '<>', EnumStaff::IS_OWNER)
            ->where('id', '<>', Auth::id())
            ->delete();
    }

    public function updateStaffByEmail($email, $data)
    {
        return $this->model
            ->where('email', $email)
            ->update($data);
    }

    public function resetPassword($email, string $password)
    {
        return $this->model
            ->where('email', $email)
            ->update(['password' => $password]);
    }

    public function changeEmail($oldEmail, $newEmail)
    {
        return $this->model
            ->where('email', $oldEmail)
            ->update(['email' => $newEmail]);
    }

    public function getInfoStaff($staffId, $fields)
    {
        return $this->model
            ->where('id', $staffId)
            ->first($fields);
    }
}
