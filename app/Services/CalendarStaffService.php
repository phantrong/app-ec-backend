<?php

namespace App\Services;

use App\Enums\EnumBookingStatus;
use App\Models\Booking;
use App\Models\CalendarStaff;
use App\Models\Customer;
use App\Models\Staff;
use App\Repositories\CalendarStaff\CalendarStaffRepository;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CalendarStaffService
{
    private $calendarStaffRepository;

    const RECEPTION_MINUTE_LENGTH = 30;

    /**
     * CalendarStaffService constructor.
     *
     * @param CalendarStaffRepository $calendarStaffRepository
     */
    public function __construct(
        CalendarStaffRepository $calendarStaffRepository
    ) {
        $this->calendarStaffRepository = $calendarStaffRepository;
    }

    /**
     * Get calendar list.
     *
     * @param array $receptionDate
     * @return array|Collection
     */
    public function getCalendarList(array $receptionDate)
    {
        $condition = [
            'reception_dates' => $receptionDate,
            'store_id' => Auth::user()->store_id,
        ];
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();
        $calendarColumns = [
            "$tblCalendarStaff.id",
            'staff_id',
            "$tblStaff.name AS staff_name",
            'reception_date',
            'reception_start_time',
            'reception_end_time',
            'customer_id',
            'bookings.status',
        ];
        $calendarList = $this->calendarStaffRepository->getList($condition, $calendarColumns);

        $calendarListArr = $calendarList->groupBy(['reception_date', 'reception_start_time'])->toArray();
        $totalQuantity = [];
        $calendarsArr = [];
        $currentReceptionDateTime = null;
        foreach ($calendarList as $calendar) {
            if ($currentReceptionDateTime != "$calendar->reception_date $calendar->reception_start_time") {
                $totalQuantity['total'][$calendar->reception_date][$calendar->reception_start_time] = count(
                    $calendarListArr[$calendar->reception_date][$calendar->reception_start_time]
                );
                [$bookings, $totalQuantity, $currentReceptionDateTime] = $this->getBookedCalendar(
                    $calendarListArr,
                    $calendar,
                    $totalQuantity,
                    $currentReceptionDateTime
                );
                $calendarsArr[$calendar->reception_date_value][$calendar->reception_start_time_value] = [
                    'total' => $totalQuantity['total'][$calendar->reception_date][$calendar->reception_start_time],
                    'booked' =>
                        $totalQuantity['booked'][$calendar->reception_date][$calendar->reception_start_time] ?? 0,
                    'reception_start_time' => $calendar->reception_start_time,
                    'reception_end_time' => $calendar->reception_end_time,
                    'bookings' => !empty($bookings) ? $bookings : [[
                        'id' => $calendar->id,
                        'staff_id' => $calendar->staff_id,
                        'staff_name' => $calendar->staff_name,
                        'customer_id' => null,
                    ]],
                ];
            }
        }

        return $calendarsArr;
    }

    /**
     * Add calendar.
     *
     * @param array $input
     * @return mixed
     */
    public function addCalendar(array $input)
    {
        $attributes = [];
        $ordinal = 0;
        foreach ($input['reception_dates'] as $receptionDate) {
            foreach ($input['reception_times'] as $receptionTime) {
                $attributes[$ordinal]['staff_id'] = $input['staff_id'];
                $attributes[$ordinal]['reception_date'] = $receptionDate;
                $attributes[$ordinal]['reception_start_time'] = $receptionTime;
                $attributes[$ordinal]['reception_end_time'] = date(
                    'H:i',
                    strtotime(
                        "{$attributes[$ordinal]['reception_start_time']} +" . self::RECEPTION_MINUTE_LENGTH . 'minute'
                    )
                );
                $attributes[$ordinal]['created_at'] = now();
                $ordinal++;
            }
        }

        return $this->calendarStaffRepository->insert($attributes);
    }

    /**
     * Get booked calendar detail.
     *
     * @param int $id
     * @return Builder|Model|array
     */
    public function getBookedCalendarDetail(int $id)
    {
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();
        $tblBooking = Booking::getTableName();
        $columns = [
            "$tblCalendarStaff.id",
            "$tblStaff.name AS staff_name",
            "$tblCustomer.name AS customer_name",
            "$tblCustomer.surname AS customer_surname",
            "$tblCustomer.phone AS customer_phone",
            'reception_date',
            'reception_start_time',
            'reception_end_time',
            "$tblBooking.status AS booking_status",
            'customer_note',
        ];

        return $this->calendarStaffRepository->getBookedCalendarDetail($id, $columns);
    }

    /**
     * Get calendar of staff.
     *
     * @param array $input
     * @return mixed
     */
    public function getCalendarOfStaff(array $input)
    {
        $input['canceled_booking_status'] = EnumBookingStatus::CANCELED_BOOKING_STATUS;
        $columns = [
            'staff_id',
            'reception_date',
            'reception_start_time',
        ];
        $calendarList = $this->calendarStaffRepository->getCalendarOfStaff($input, $columns);
        $bookedCalendarList = $this->calendarStaffRepository->getBookedCalendarList($input, 'reception_start_time');
        if ($calendarList->isEmpty()) {
            return false;
        }

        $receptionTimesArr = $calendarList->pluck('reception_start_time_value');
        $response = Arr::only($input, [
            'staff_id',
            'reception_date',
        ]);
        $response['reception_times'] = $receptionTimesArr;
        $response['booked_time'] = $bookedCalendarList->pluck('reception_start_time_value')->toArray();

        return $response;
    }

    /**
     * Change calendar.
     *
     * @param array $input
     * @return array
     * @throws Exception
     */
    public function changeCalendar(array $input)
    {
        $calendarList = $this->calendarStaffRepository->getListActiveByStaffId(
            $input,
            [CalendarStaff::getTableName() . '.id', 'reception_start_time']
        );
        if ($calendarList->isEmpty()) {
            return responseArrError(JsonResponse::HTTP_NOT_ACCEPTABLE, [config('errorCodes.calendar_staff.empty')]);
        }

        $this->createOrDeleteCalendar($input, $calendarList);
        return [];
    }

    /**
     * Create or delete calendar staff.
     *
     * @param array $input
     * @param $calendarList
     * @throws Exception
     */
    private function createOrDeleteCalendar(array $input, $calendarList)
    {
        // Set multi attributes to create or update calendar_staff
        $condition = [];
        foreach ($input['reception_times'] as $key => $receptionTime) {
            $condition[$key]['staff_id'] = $input['staff_id'];
            $condition[$key]['reception_date'] = $input['reception_date'];
            $condition[$key]['reception_start_time'] = $receptionTime;
            $condition[$key]['reception_end_time'] = date(
                'H:i',
                strtotime(
                    "{$condition[$key]['reception_start_time']} +" . self::RECEPTION_MINUTE_LENGTH . 'minute'
                )
            );
            $condition[$key]['created_at'] = now();
        }

        $receptionStartTimeArr = $calendarList->pluck('reception_start_time_value')->toArray();
        $calendarIdArr = $calendarList->pluck('id')->toArray();

        // Remove calendar_staff which does not need create/delete
        foreach ($condition as $key => $value) {
            if (in_array($condition[$key]['reception_start_time'], $receptionStartTimeArr)) {
                $index = array_search($condition[$key]['reception_start_time'], $receptionStartTimeArr);
                unset($receptionStartTimeArr[$index]);
                unset($calendarIdArr[$index]);
                unset($condition[$key]);
            }
        }

        try {
            DB::beginTransaction();
            $this->calendarStaffRepository->deleteMulti($calendarIdArr);
            $this->calendarStaffRepository->insert($condition);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function checkCalendarStaff($staffId, $startTime)
    {
        return $this->calendarStaffRepository->checkCalendarStaff($staffId, $startTime);
    }

    /**
     * Get booked calendar.
     *
     * @param $bookingListArr
     * @param $calendar
     * @param $totalQuantity
     * @param $currentReceptionDate
     * @return array
     */
    private function getBookedCalendar($bookingListArr, $calendar, $totalQuantity, $currentReceptionDate)
    {
        $bookings = [];
        $receptionDateArr = array_keys($bookingListArr);
        if (in_array($calendar->reception_date, $receptionDateArr)) {
            $receptionDate = $calendar->reception_date;
            $receptionTimeArr = array_keys($bookingListArr[$receptionDate]);
            if (in_array($calendar->reception_start_time, $receptionTimeArr)) {
                $receptionTime = $calendar->reception_start_time;
                $totalQuantity['booked'][$calendar->reception_date][$calendar->reception_start_time] =
                    count(array_filter(
                        $bookingListArr[$calendar->reception_date][$calendar->reception_start_time],
                        function ($item) {
                            return !in_array($item['status'], EnumBookingStatus::CANCELED_BOOKING_STATUS)
                                && $item['status'] !== null;
                        }
                    ));

                $bookingValueListArr = $bookingListArr[$calendar->reception_date][$receptionTime];
                foreach ($bookingValueListArr as $booking) {
                    if ($booking['reception_start_time'] == $calendar->reception_start_time) {
                        $bookings[] = [
                            'id' => $booking['id'],
                            'staff_id' => $booking['staff_id'],
                            'staff_name' => $booking['staff_name'],
                            'customer_id' => in_array($booking['status'], EnumBookingStatus::BOOKED_BOOKING_STATUS) ?
                                $booking['customer_id'] :
                                null,
                        ];
                    }
                }
                $currentReceptionDate = "$calendar->reception_date $calendar->reception_start_time";
            }
        }

        return [$bookings, $totalQuantity, $currentReceptionDate];
    }
}
