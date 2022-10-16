<?php

namespace App\Repositories\Booking;

use App\Enums\EnumBookingStatus;
use App\Enums\EnumIsCallVideo;
use App\Enums\EnumVideoCallType;
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
use Illuminate\Support\Facades\DB;

class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    const PER_PAGE = 10;
    const PAGE_DEFAULT = 1;
    const MINUTE_EXTENSION = 30;

    public function getModel()
    {
        return Booking::class;
    }

    /**
     * Get booked booking list.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getBookedBookingList(array $condition, $columns = ['*'])
    {
        $tblBooking = $this->model->getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();

        return $this->model
            ->join($tblCalendarStaff, "$tblBooking.calendar_staff_id", '=', "$tblCalendarStaff.id")
            ->join($tblStaff, "$tblCalendarStaff.staff_id", '=', "$tblStaff.id")
            ->when(isset($condition['reception_date']), function ($query) use ($tblCalendarStaff, $condition) {
                return $query->where("$tblCalendarStaff.reception_date", '=', $condition['reception_date']);
            })
            ->when(isset($condition['store_id']), function ($query) use ($tblStaff, $condition) {
                $query->where("$tblStaff.store_id", '=', $condition['store_id']);
            })
            ->when(isset($condition['canceled_booking_status']), function ($query) use ($tblBooking, $condition) {
                $query->whereNotIn("$tblBooking.status", $condition['canceled_booking_status']);
            })
            ->orderByRaw("
                reception_date,
                reception_start_time,
                $tblCalendarStaff.id
            ")
            ->get($columns);
    }

    /**
     * Get reservation history of staff.
     *
     * @param array $input
     * @param array $columns
     * @param int $paginate
     * @return LengthAwarePaginator|Collection
     */
    public function getReservationHistoryOfStaff(array $input, $columns = ['*'], $paginate = true)
    {
        $perPage = $input['per_page'] ?? self::PER_PAGE;
        $page = $input['page'] ?? self::PAGE_DEFAULT;

        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();

        $query = $this->getReservationHistory($input, $columns);
        if (isset($input['store_id'])) {
            $query->where("$tblStaff.store_id", $input['store_id']);
        }
        if (isset($input['customer_name'])) {
            $name = str_replace(' ', '', $input['customer_name']);
            $query->whereRaw("CONCAT($tblCustomer.name, $tblCustomer.surname) LIKE '%$name%'");
        }

        if ($paginate) {
            return $query->paginate($perPage, $columns, 'page', $page);
        }
        return $query->get();
    }

    /**
     * Get reservation history of customer.
     *
     * @param array $input
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getReservationHistoryOfCustomer(array $input, $columns = ['*'], $paginate = true)
    {
        $perPage = $input['per_page'] ?? self::PER_PAGE;
        $page = $input['page'] ?? self::PAGE_DEFAULT;

        $tblStaff = Staff::getTableName();
        $tblStore = Store::getTableName();
        $tblCustomer = Customer::getTableName();

        $query = $this->getReservationHistory($input, $columns);
        $query = $query->join($tblStore, "$tblStaff.store_id", '=', "$tblStore.id")
            ->whereNull("$tblStore.deleted_at");
        if (isset($input['customer_id'])) {
            $query->where("$tblCustomer.id", $input['customer_id']);
        }
        if (isset($input['store_name'])) {
            $query->where("$tblStore.name", 'LIKE', "%{$input['store_name']}%");
        }
        if ($paginate) {
            return $query->paginate($perPage, $columns, 'page', $page);
        }
        return $query->get($columns);
    }

    /**
     * Get video call type of end user has quantity.
     *
     * @param $customerId
     * @param $storeId
     * @return Builder[]|Collection
     */
    public function getVideoCallTypeEndUserHasQuantity($customerId = null, $storeId = null)
    {
        $tblBooking = $this->model->getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();

        return $this->model
            ->selectRaw("
                customer_video_call_type AS video_call_type,
                COUNT(customer_video_call_type) AS quantity
            ")
            ->join($tblCalendarStaff, "$tblBooking.calendar_staff_id", '=', "$tblCalendarStaff.id")
            ->join($tblStaff, "$tblCalendarStaff.staff_id", '=', "$tblStaff.id")
            ->when($storeId, function ($query) use ($tblStaff, $storeId) {
                return $query->where("$tblStaff.store_id", $storeId);
            })
            ->when($customerId, function ($query) use ($tblBooking, $customerId) {
                return $query->where("$tblBooking.customer_id", $customerId);
            })
            ->groupBy('customer_video_call_type')
            ->orderBy('customer_video_call_type')
            ->get();
    }

    /**
     * Get booking detail.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder|Model
     */
    public function getDetail(array $condition, $columns = ['*'])
    {
        $tblBooking = $this->model->getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();
        $tblStore = Store::getTableName();
        return $this->model
            ->join($tblCalendarStaff, "$tblBooking.calendar_staff_id", '=', "$tblCalendarStaff.id")
            ->join($tblStaff, "$tblCalendarStaff.staff_id", '=', "$tblStaff.id")
            ->join($tblCustomer, "$tblBooking.customer_id", '=', "$tblCustomer.id")
            ->join($tblStore, "$tblStore.id", '=', "$tblStaff.store_id")
            ->whereNull("$tblCalendarStaff.deleted_at")
            ->where("$tblBooking.id", '=', $condition['booking_id'])
            ->when(isset($condition['booking_status']), function ($query) use ($tblBooking, $condition) {
                return $query->whereIn("$tblBooking.status", $condition['booking_status']);
            })
            ->first($columns);
    }

    /**
     * Get booking detail by condition.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getDetailByCondition(array $condition, $columns = ['*'])
    {
        $tblBooking = $this->model->getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();

        return $this->model
            ->rightJoin($tblCalendarStaff, "$tblBooking.calendar_staff_id", '=', "$tblCalendarStaff.id")
            ->whereNull("$tblCalendarStaff.deleted_at")
            ->when(isset($condition['booking_id']), function ($query) use ($tblBooking, $condition) {
                return $query->where("$tblBooking.id", '=', $condition['booking_id']);
            })
            ->when(isset($condition['customer_id']), function ($query) use ($condition) {
                return $query->where('customer_id', '=', $condition['customer_id']);
            })
            ->when(isset($condition['reception_date']), function ($query) use ($condition) {
                return $query->where('reception_date', $condition['reception_date']);
            })
            ->when(isset($condition['reception_time']), function ($query) use ($condition) {
                return $query->where('reception_start_time', date('H:i:s', strtotime($condition['reception_time'])));
            })
            ->when(
                isset($condition['booking_status']) && !empty($condition['booking_status']),
                function ($query) use ($tblBooking, $condition) {
                    return $query->where(function ($q) use ($tblBooking, $condition) {
                        $q->whereIn("$tblBooking.status", $condition['booking_status'])
                            ->orWhereNull("$tblBooking.status");
                    });
                }
            )
            ->first($columns);
    }

    /**
     * Cancel booking.
     *
     * @param array $condition
     * @return int
     */
    public function cancelBooking(array $condition)
    {
        return $this->model
            ->where('id', '=', $condition['booking_id'])
            ->where('customer_id', '=', $condition['customer_id'])
            ->whereIn('status', $condition['status_list'])
            ->update([
                'status' => EnumBookingStatus::STATUS_CANCEL,
            ]);
    }

    /**
     * Query of getting reservation history condition.
     *
     * @param array $input
     * @param $columns
     * @return mixed
     */
    private function getReservationHistory(array $input, $columns)
    {
        $tblBooking = $this->model->getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();

        $bookingStatusListArr = [
            EnumBookingStatus::STATUS_PROCESSING,
            EnumBookingStatus::STATUS_CONFIRM_VIDEO_CALL_TYPE,
            EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE,
            EnumBookingStatus::STATUS_COMPLETE,
            EnumBookingStatus::STATUS_CANCEL,
            EnumBookingStatus::STATUS_CANCEL_FORCE,
        ];
        $bookingStatusList = implode(',', $bookingStatusListArr);

        $query = $this->model->select($columns)
            ->join($tblCalendarStaff, "$tblBooking.calendar_staff_id", '=', "$tblCalendarStaff.id")
            ->join($tblStaff, "$tblCalendarStaff.staff_id", '=', "$tblStaff.id")
            ->join($tblCustomer, "$tblBooking.customer_id", '=', "$tblCustomer.id")
            ->orderByRaw("
                FIELD($tblBooking.status,$bookingStatusList),
                reception_date,
                reception_start_time
            ")
            ->groupBy("$tblBooking.id");
        if (isset($input['booking_status']) && $input['booking_status']) {
            $query->where("$tblBooking.status", $input['booking_status']);
        }
        if (isset($input['booking_type'])
            && $input['booking_type'] != EnumBookingStatus::STATUS_UN_CONFIRM
            && $input['booking_type']
        ) {
            $query->where("$tblBooking.store_video_call_type", $input['booking_type']);
        }
        if (isset($input['booking_type'])
            && $input['booking_type'] == EnumBookingStatus::STATUS_UN_CONFIRM
        ) {
            $query->whereNull("$tblBooking.store_video_call_type");
        }
        if (isset($input['booking_type_customer']) && $input['booking_type_customer']) {
            $query->where("$tblBooking.customer_video_call_type", $input['booking_type_customer']);
        }
        if (isset($input['start_date'])) {
            $query->where('reception_date', '>=', date('Y-m-d', strtotime($input['start_date'])));
        }
        if (isset($input['end_date'])) {
            $query->where('reception_date', '<=', date('Y-m-d', strtotime($input['end_date'])));
        }

        return $query;
    }

    public function getListVideoHomePage(array $request)
    {
        $tableStaff = Staff::getTableName();
        $tableStore = Store::getTableName();
        $tableCalendarStaff = CalendarStaff::getTableName();
        $tableBooking = $this->model->getTablename();
        $tblCustomer = Customer::getTableName();
        $perPage = $request['per_page'] ?? self::PER_PAGE;
        $storeId = $request['store_id'] ?? null;
        $keyWord = $request['key_word'] ?? null;
        $sort = $request['sort'] ?? null;
        $now = now()->format('Y-m-d H:i:s');
        return $this->model
            ->select(
                "$tableBooking.id",
                "$tableBooking.status",
                "$tableCalendarStaff.reception_date",
                "$tableCalendarStaff.reception_start_time",
                "$tableStore.name",
                "$tableStore.id as store_id",
                "$tableBooking.view_total",
                "$tableStore.avatar as avatar_store",
                "$tblCustomer.avatar as avatar_customer",
                DB::raw(
                    "CASE WHEN start_time_actual IS NOT NULL THEN
                    TIMESTAMPDIFF(second, start_time_actual, now())
                       END as time_passed"
                )
            )
            ->join($tableCalendarStaff, "$tableCalendarStaff.id", '=', "$tableBooking.calendar_staff_id")
            ->join($tableStaff, "$tableStaff.id", '=', "$tableCalendarStaff.staff_id")
            ->join($tableStore, "$tableStore.id", '=', "$tableStaff.store_id")
            ->join($tblCustomer, "$tblCustomer.id", '=', "$tableBooking.customer_id")
            ->where("$tableBooking.status", EnumBookingStatus::STATUS_PROCESSING)
            ->where("$tableBooking.final_video_call_type", EnumVideoCallType::TYPE_PUBLIC)
            ->whereRaw("CONCAT(reception_date, ' ',reception_end_time) > '$now'")
            ->whereNull("$tableCalendarStaff.deleted_at")
            ->whereNull("$tableStaff.deleted_at")
            ->whereNull("$tableStore.deleted_at")
            ->when($storeId, function ($query) use ($storeId, $tableStore) {
                return $query->where("$tableStore.id", $storeId);
            })
            ->when($keyWord, function ($query) use ($keyWord, $tableStore) {
                return $query->where("$tableStore.name", 'like', '%' . $keyWord . '%');
            })
            ->when($sort, function ($query) use ($sort, $tableBooking) {
                switch ($sort) {
                    case EnumIsCallVideo::SORT_BY_OLD:
                        return $query->orderBy("$tableBooking.created_at");
                    case EnumIsCallVideo::SORT_BY_VIEW:
                        return $query->orderByDesc("$tableBooking.view_total");
                    default:
                        return $query->orderByDesc("$tableBooking.created_at");
                }
            })
            ->paginate($perPage);
    }

    /**
     * Update store video call type.
     *
     * @param array $condition
     * @param array $values
     * @return int
     */
    public function updateVideoCallType(array $condition, array $values)
    {
        return $this->model
            ->where('id', $condition['booking_id'])
            ->whereIn('status', $condition['booking_status'])
            ->update($values);
    }

    public function checkStaffIsCallVideo($staffId)
    {
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblBooking = Booking::getTableName();
        return $this->model
            ->join($tblCalendarStaff, "$tblCalendarStaff.id", '=', "$tblBooking.calendar_staff_id")
            ->where("$tblBooking.status", EnumBookingStatus::STATUS_PROCESSING)
            ->where("$tblCalendarStaff.staff_id", $staffId)
            ->exists();
    }

    public function updateActualTimeVideo()
    {
        $timeExtension = self::MINUTE_EXTENSION;
        $dateCheck = now()->subMinute($timeExtension)->format('Y-m-d H:i:s');
        $tblBooking = Booking::getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        return $this->model
            ->join($tblCalendarStaff, "$tblCalendarStaff.id", '=', "$tblBooking.calendar_staff_id")
            ->where('status', EnumBookingStatus::STATUS_COMPLETE)
            ->whereRaw(
                "'$dateCheck' > CONCAT($tblCalendarStaff.reception_date, ' ', $tblCalendarStaff.reception_end_time)"
            )
            ->update([
                'end_time_actual' => DB::raw("
                DATE_ADD(CONCAT($tblCalendarStaff.reception_date, ' ', $tblCalendarStaff.reception_end_time),
                INTERVAL $timeExtension MINUTE)")
            ]);
    }

    public function updateVideoComplete()
    {
        $dateCheck = now()->format('Y-m-d H:i:s');
        $tblBooking = Booking::getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        return $this->model
            ->join($tblCalendarStaff, "$tblCalendarStaff.id", '=', "$tblBooking.calendar_staff_id")
            ->where('status', EnumBookingStatus::STATUS_PROCESSING)
            ->whereRaw(
                "'$dateCheck' > CONCAT($tblCalendarStaff.reception_date, ' ', $tblCalendarStaff.reception_end_time)"
            )
            ->update([
                'status' => EnumBookingStatus::STATUS_COMPLETE
            ]);
    }

    public function getBookingHasEndTime()
    {
        $now = now()->format('Y-m-d');
        $yesterday = now()->subDay(1)->format('Y-m-d');
        return $this->model
            ->select('room_id')
            ->whereDate('end_time_actual', $now)
            ->orWhereDate('end_time_actual', $yesterday)
            ->get();
    }
}
