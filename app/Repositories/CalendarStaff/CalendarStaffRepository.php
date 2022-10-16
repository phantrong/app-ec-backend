<?php

namespace App\Repositories\CalendarStaff;

use App\Enums\EnumBookingStatus;
use App\Models\Booking;
use App\Models\CalendarStaff;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Store;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CalendarStaffRepository extends BaseRepository implements CalendarStaffRepositoryInterface
{
    public function getModel()
    {
        return CalendarStaff::class;
    }

    /**
     * Get list of calendar.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getList(array $condition, $columns = ['*'])
    {
        $tblCalendarStaff = $this->model->getTableName();
        $tblStaff = Staff::getTableName();

        return $this->model
            ->leftJoinSub(
                Booking::whereNotIn('status', EnumBookingStatus::CANCELED_BOOKING_STATUS)
                    ->select([
                        'calendar_staff_id',
                        'customer_id',
                        'status',
                    ]),
                'bookings',
                "$tblCalendarStaff.id",
                '=',
                'bookings.calendar_staff_id'
            )
            ->join($tblStaff, "$tblCalendarStaff.staff_id", '=', "$tblStaff.id")
            ->whereBetween('reception_date', $condition['reception_dates'])
            ->when(isset($condition['store_id']), function ($query) use ($tblStaff, $condition) {
                $query->where("$tblStaff.store_id", '=', $condition['store_id']);
            })
            ->orderByRaw("
                reception_date,
                reception_start_time,
                $tblCalendarStaff.id
            ")
            ->get($columns);
    }

    /**
     * Get calendar list by condition.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getListByCondition(array $condition, $columns = ['*'])
    {
        $tblCalendarStaff = $this->model->getTableName();
        $tblStaff = Staff::getTableName();

        return $this->model
            ->leftJoinSub(
                Booking::whereNotIn('status', EnumBookingStatus::CANCELED_BOOKING_STATUS)
                    ->select([
                        'calendar_staff_id',
                        'customer_id',
                        'status',
                    ]),
                'bookings',
                "$tblCalendarStaff.id",
                '=',
                'bookings.calendar_staff_id'
            )
            ->join($tblStaff, "$tblCalendarStaff.staff_id", '=', "$tblStaff.id")
            ->when(isset($condition['reception_date']), function ($query) use ($condition) {
                return $query->where('reception_date', $condition['reception_date']);
            })
            ->when(isset($condition['reception_time']), function ($query) use ($condition) {
                return $query->where('reception_start_time', date('H:i:s', strtotime($condition['reception_time'])));
            })
            ->when(isset($condition['store_id']), function ($query) use ($tblStaff, $condition) {
                $query->where("$tblStaff.store_id", '=', $condition['store_id']);
            })
            ->get($columns);
    }

    /**
     * Get calendar_staff list by reception date and reception start time.
     *
     * @param array $input
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getListByReceptionDateTime(array $input, $columns = ['*'])
    {
        $tblCalendarStaff = $this->model->getTableName();
        $tblStaff = Staff::getTableName();
        $tblStore = Store::getTableName();

        return $this->model->join($tblStaff, "$tblCalendarStaff.staff_id", '=', "$tblStaff.id")
            ->join($tblStore, "$tblStaff.store_id", '=', "$tblStore.id")
            ->where('store_id', $input['store_id'])
            ->where('reception_date', $input['reception_date'])
            ->where('reception_start_time', $input['reception_time'])
            ->when(isset($input['booked_calendar_id']), function ($query) use ($tblCalendarStaff, $input) {
                $query->whereNotIn("$tblCalendarStaff.id", $input['booked_calendar_id']);
            })
            ->whereNull("$tblStaff.deleted_at")
            ->get($columns);
    }

    /**
     * Get calendar_staff list which have not booked reception time by staff id and reception date.
     *
     * @param array $condition
     * @param array $columns
     * @return object
     */
    public function getListActiveByStaffId(array $condition, $columns = ['*'])
    {
        return $this->model
            ->when(isset($condition['staff_id']), function ($query) use ($condition) {
                $query->where('staff_id', $condition['staff_id']);
            })
            ->when(isset($condition['reception_date']), function ($query) use ($condition) {
                $query->where('reception_date', $condition['reception_date']);
            })
            ->when(isset($condition['booked_calendar_id']), function ($query) use ($condition) {
                $query->whereNotIn('id', $condition['booked_calendar_id']);
            })
            ->distinct()
            ->get($columns);
    }

    /**
     * Get calendar of staff.
     *
     * @param array $input reception date and staff id
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getCalendarOfStaff(array $input, $columns = ['*'])
    {
        return $this->model->where('staff_id', $input['staff_id'])
            ->where('reception_date', $input['reception_date'])
            ->get($columns);
    }

    /**
     * Get booked calendar.
     *
     * @param array $input reception date and staff id
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getBookedCalendarList(array $input, $columns = ['*'])
    {
        $tblCalendarStaff = $this->model->getTableName();
        $tblBooking = Booking::getTableName();

        return $this->model
            ->leftJoin($tblBooking, "$tblCalendarStaff.id", '=', "$tblBooking.calendar_staff_id")
            ->where('staff_id', $input['staff_id'])
            ->where('reception_date', $input['reception_date'])
            ->when(isset($input['canceled_booking_status']), function ($query) use ($tblBooking, $input) {
                $query->whereNotIn("$tblBooking.status", $input['canceled_booking_status']);
            })
            ->get($columns);
    }

    /**
     * Get booked calendar detail.
     *
     * @param int $id
     * @param array $columns
     * @return Builder|Model
     */
    public function getBookedCalendarDetail(int $id, $columns = ['*'])
    {
        $tblCalendarStaff = $this->model->getTableName();
        $tblBooking = Booking::getTableName();
        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();

        return $this->model
            ->join($tblBooking, "$tblCalendarStaff.id", '=', "$tblBooking.calendar_staff_id")
            ->join($tblStaff, "$tblCalendarStaff.staff_id", '=', "$tblStaff.id")
            ->join($tblCustomer, "$tblBooking.customer_id", '=', "$tblCustomer.id")
            ->whereNull("$tblBooking.deleted_at")
            ->where("$tblCalendarStaff.id", '=', $id)
            ->whereNotIn("$tblBooking.status", EnumBookingStatus::CANCELED_BOOKING_STATUS)
            ->first($columns);
    }

    /**
     * Update or create calendar_staff.
     *
     * @param $id
     * @param array $values
     * @return Builder|Model
     */
    public function updateOrCreate($id, array $values)
    {
        return $this->model->updateOrCreate(['id' => $id], $values);
    }

    /**
     * Delete multi calendar_staff.
     *
     * @param array $ids
     * @return mixed
     */
    public function deleteMulti(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function checkCalendarStaff($staffId, $startTime)
    {
        $startTime = Carbon::parse($startTime);
        $date = $startTime->format('Y-m-d');
        $time = $startTime->format('H:i:s');
        return $this->model
            ->where('staff_id', $staffId)
            ->whereDate('reception_date', $date)
            ->where('reception_start_time', '<=', $time)
            ->where('reception_end_time', '>=', $time)
            ->exists();
    }

    public function checkStaffHasCalendar($staffId)
    {
        $now = now()->format('Y-m-d H:i:s');
        return $this->model
            ->whereRaw("CONCAT(reception_date, ' ', reception_start_time) >= '$now'")
            ->where('staff_id', $staffId)
            ->exists();
    }
}
