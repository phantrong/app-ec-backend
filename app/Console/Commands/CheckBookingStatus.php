<?php

namespace App\Console\Commands;

use App\Enums\EnumBookingStatus;
use App\Jobs\SendMailCancelForceBooking;
use App\Jobs\SendMailConfirmBooking;
use App\Models\Booking;
use App\Models\CalendarStaff;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckBookingStatus extends Command
{
    const MINUTE_BEFORE_RECEPTION_TIME = 30;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:booking_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check cancel force booking';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::channel('booking')->info('====================BEGIN BATCH CHECK CANCEL FORCE BOOKING==================');

        $bookingListArr = $this->getPendingConfirmBookingList();
        if (empty($bookingListArr)) {
            Log::channel('booking')->info('[No checked data]');
        } else {
            foreach ($bookingListArr as $key => $bookingList) {
                switch ($key) {
                    case 'confirm':
                        Log::channel('booking')->info('[Confirm]');
                        $this->checkConfirmBooking($bookingList);
                        break;
                    case 'cancel_force':
                        Log::channel('booking')->info('[Cancel force]');
                        $this->checkCancelForceBooking($bookingList);
                        break;
                    case 'cancel':
                        Log::channel('booking')->info('[Cancel]');
                        $this->checkCancelBooking($bookingList);
                        break;
                    default:
                        break;
                }
            }
        }

        Log::channel('booking')->info('====================END BATCH CHECK CANCEL FORCE BOOKING==================');
    }

    /**
     * Check confirm booking.
     *
     * @param array $bookingListArr
     */
    private function checkConfirmBooking(array $bookingListArr)
    {
        foreach ($bookingListArr as $booking) {
            $mailInput = [
                'email' => $booking->staff_email,
                'name_staff' => $booking->staff_name,
                'store_name' => $booking->store_name,
                'name_user' => $booking->customer_surname . $booking->customer_name,
                'reception_start_time' => Carbon::parse("$booking->reception_date $booking->reception_start_time"),
                'reception_end_time' => Carbon::parse("$booking->reception_date $booking->reception_end_time"),
            ];
            SendMailConfirmBooking::dispatch($mailInput);
        }
    }

    /**
     * Check cancel force booking.
     *
     * @param array $bookingListArr
     */
    private function checkCancelForceBooking(array $bookingListArr)
    {
        $calendarStaffIdArr = array_column($bookingListArr, 'calendar_staff_id');
        Booking::whereIn('calendar_staff_id', $calendarStaffIdArr)
            ->whereIn('status', [
                EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE,
            ])
            ->update([
                'status' => EnumBookingStatus::STATUS_CANCEL_FORCE,
            ]);

        // Send mail
        foreach ($bookingListArr as $booking) {
            $mailInput = [
                'store' => [
                    'email' => $booking->staff_email,
                    'name' => $booking->staff_name,
                    'store_name' => $booking->store_name,
                    'start_time' => Carbon::parse("$booking->reception_date $booking->reception_start_time"),
                    'end_time' => Carbon::parse("$booking->reception_date $booking->reception_end_time"),
                ],
                'customer' => [
                    'email' => $booking->customer_email,
                    'name' => "$booking->customer_surname $booking->customer_name",
                    'store_name' => $booking->store_name,
                    'start_time' => Carbon::parse("$booking->reception_date $booking->reception_start_time"),
                    'end_time' => Carbon::parse("$booking->reception_date $booking->reception_end_time"),
                    'send_mail' => $booking->send_mail
                ]
            ];
            SendMailCancelForceBooking::dispatch($mailInput);
        }
    }

    /**
     * Check cancel booking.
     *
     * @param array $calendarIdsArr
     */
    private function checkCancelBooking(array $calendarIdsArr)
    {
        Booking::whereIn('calendar_staff_id', $calendarIdsArr)
            ->whereIn('status', [
                EnumBookingStatus::STATUS_CONFIRM_VIDEO_CALL_TYPE,
            ])
            ->update([
                'status' => EnumBookingStatus::STATUS_CANCEL,
            ]);
    }

    /**
     * Get booking list with status is pending confirm video call type from store and after reception date time.
     *
     * @return array
     */
    private function getPendingConfirmBookingList()
    {
        $tblBooking = Booking::getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();
        $tblStore = Store::getTableName();
        $bookingListArr = [];
        $dateTimeNow = now()->format('Y-m-d H:i:s');
        $bookings = Booking::select(
            "$tblBooking.calendar_staff_id",
            'reception_date',
            'reception_start_time',
            'reception_end_time',
            "$tblBooking.status AS booking_status",
            "$tblStaff.email AS staff_email",
            "$tblStaff.name AS staff_name",
            "$tblCustomer.email AS customer_email",
            "$tblCustomer.send_mail",
            "$tblCustomer.name AS customer_name",
            "$tblCustomer.surname AS customer_surname",
            "$tblStore.name as store_name"
        )
            ->join($tblCalendarStaff, "$tblCalendarStaff.id", '=', "$tblBooking.calendar_staff_id")
            ->join($tblStaff, 'staff_id', '=', "$tblStaff.id")
            ->join($tblCustomer, 'customer_id', '=', "$tblCustomer.id")
            ->join($tblStore, "$tblStore.id", '=', "$tblStaff.store_id")
            ->whereIn("$tblBooking.status", [
                EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE,
                EnumBookingStatus::STATUS_CONFIRM_VIDEO_CALL_TYPE
            ])
            ->where('reception_date', '=', now()->format('Y-m-d'))
            ->whereNull("$tblBooking.deleted_at")
            ->whereNull("$tblStaff.deleted_at")
            ->whereNull("$tblCustomer.deleted_at")
            ->get();
        foreach ($bookings as $booking) {
            $receptionStartDateTime = "$booking->reception_date $booking->reception_start_time";
            $receptionEndDateTime = "$booking->reception_date $booking->reception_end_time";
            if ($booking->booking_status == EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE) {
                if ($dateTimeNow > $receptionStartDateTime) {
                    $bookingListArr['cancel_force'][] = $booking;
                } elseif (now()->addMinutes(self::MINUTE_BEFORE_RECEPTION_TIME)
                        ->format('Y-m-d H:i:00') == $receptionStartDateTime) {
                    $bookingListArr['confirm'][] = $booking;
                }
            } elseif ($booking->booking_status == EnumBookingStatus::STATUS_CONFIRM_VIDEO_CALL_TYPE
                && now()->subMinutes()->format('Y-m-d H:i:s') >= $receptionEndDateTime) {
                $bookingListArr['cancel'][] = $booking->calendar_staff_id;
            }
        }

        return $bookingListArr;
    }
}
