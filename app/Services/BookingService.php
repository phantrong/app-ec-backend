<?php

namespace App\Services;

use App\Enums\EnumBookingStatus;
use App\Enums\EnumInitType;
use App\Enums\EnumVideoCallType;
use App\Goez\SocketIO\Emitter;
use App\Jobs\JobSendMailNotifyBookingPrivate;
use App\Jobs\SendMailCancelBooking;
use App\Models\Booking;
use App\Models\CalendarStaff;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Store;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\CalendarStaff\CalendarStaffRepository;
use App\Repositories\Customer\CustomerRepository;
use App\Repositories\Messenger\MessengerRepository;
use App\Repositories\Staff\StaffRepository;
use App\Repositories\VideoChat\VideoChatRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Predis\Client;
use Throwable;
use Illuminate\Support\Str;

class BookingService
{
    private $calendarStaffRepository;
    private $bookingRepository;
    private $videoChatRepository;
    private $staffRepository;
    private CustomerRepository $customerRepository;
    private MessengerRepository $messengerRepository;


    const PREVIOUS_TIME_START = 10;

    /**
     * CalendarStaffService constructor.
     *
     * @param CalendarStaffRepository $calendarStaffRepository
     * @param BookingRepository $bookingRepository
     * @param VideoChatRepository $videoChatRepository
     * @param StaffRepository $staffRepository
     */
    public function __construct(
        CalendarStaffRepository $calendarStaffRepository,
        BookingRepository       $bookingRepository,
        VideoChatRepository     $videoChatRepository,
        StaffRepository         $staffRepository,
        CustomerRepository      $customerRepository,
        MessengerRepository     $messengerRepository
    ) {
        $this->calendarStaffRepository = $calendarStaffRepository;
        $this->bookingRepository = $bookingRepository;
        $this->videoChatRepository = $videoChatRepository;
        $this->staffRepository = $staffRepository;
        $this->customerRepository = $customerRepository;
        $this->messengerRepository = $messengerRepository;
    }

    /**
     * Get booking list for customer.
     *
     * @param array $receptionDateArr
     * @param int $storeId
     * @return array|Collection
     */
    public function getBookingListForCustomer(array $receptionDateArr, ?int $storeId)
    {
        $condition = [
            'store_id' => $storeId,
            'reception_dates' => $receptionDateArr,
        ];
        $tblCalendarStaff = CalendarStaff::getTableName();
        $columns = [
            "$tblCalendarStaff.id",
            "$tblCalendarStaff.reception_date",
            "$tblCalendarStaff.reception_start_time",
            'bookings.customer_id',
        ];

        $calendars = $this->calendarStaffRepository->getList($condition, $columns);
        $calendarsArr = [];
        foreach ($calendars as $calendar) {
            $calendarsArr["{$calendar->reception_date}_{$calendar->reception_start_time_value}"] = [
                'calendar_id' => $calendar->id,
                'date' => $calendar->reception_date,
                'time' => $calendar->reception_start_time,
                'customer_id' => $calendar->customer_id,
            ];
        }

        return $calendarsArr;
    }

    /**
     * Get booking detail.
     *
     * @param int $id
     * @return Builder|Model
     */
    public function getBookingDetail(int $id)
    {
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();
        $tblBooking = Booking::getTableName();
        $columns = [
            "$tblBooking.id AS booking_id",
            "$tblBooking.room_id",
            "$tblCalendarStaff.id AS calendar_staff_id",
            "$tblStaff.name AS staff_name",
            "$tblCustomer.name AS customer_name",
            DB::raw("CONCAT($tblCustomer.surname, $tblCustomer.name) AS customer_surname"),
            "$tblCustomer.phone AS customer_phone",
            'reception_date',
            'reception_start_time',
            'reception_end_time',
            "$tblBooking.status AS booking_status",
            "$tblBooking.customer_video_call_type AS customer_type",
            'customer_note',
        ];

        return $this->bookingRepository->getDetail([
            'booking_id' => $id,
            'booking_status' => [
                EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE,
            ],
        ], $columns);
    }

    /**
     * Check booking in the past.
     *
     * @param array $condition
     * @return bool
     */
    public function checkBooking(array $condition)
    {
        $result = false;
        $oldBooking = null;
        $customerId = Auth::id();
        $tblCalendarStaff = CalendarStaff::getTableName();

        $condition['customer_id'] = $customerId;
        $columns = [
            "$tblCalendarStaff.reception_date",
            "$tblCalendarStaff.reception_start_time",
            'customer_id',
        ];
        if (isset($condition['booking_id'])) {
            $oldBooking = $this->bookingRepository->getDetailByCondition(
                Arr::only($condition, [
                    'booking_id',
                    'customer_id',
                ]) + ['booking_status' => [EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE]],
                $columns
            );
        }
        $bookings = $this->calendarStaffRepository->getListByCondition(
            Arr::only($condition, [
                'reception_date',
                'reception_time',
            ]),
            $columns
        );

        $receptionDateTime = "{$condition['reception_date']} {$condition['reception_time']}";
        $isFutureTime = $receptionDateTime > date('Y-m-d H:i');
        $bookingsArr = $bookings->pluck('customer_id')->toArray();
        $hasAvailableBooking = array_search(null, $bookingsArr) !== false;
        $hasBookedBooking = array_search($customerId, $bookingsArr) !== false;
        if (!$oldBooking) {
            if ($hasAvailableBooking && !$hasBookedBooking && $isFutureTime) {
                $result = true;
            }
        } else {
            $oldReceptionDateTime = Carbon::parse("$oldBooking->reception_date $oldBooking->reception_start_time")
                ->format('Y-m-d H:i');
            if (($oldReceptionDateTime == $receptionDateTime && $hasBookedBooking)
                || ($hasAvailableBooking && !$hasBookedBooking && $isFutureTime)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Create booking.
     *
     * @param array $input
     * @return array
     * @throws Throwable
     */
    public function createBooking(array $input)
    {
        $tblBooking = Booking::getTableName();
        $condition = [
            'store_id' => $input['store_id'],
            'reception_date' => $input['reception_date'],
            'canceled_booking_status' => EnumBookingStatus::CANCELED_BOOKING_STATUS,
        ];

        $bookings = $this->bookingRepository->getBookedBookingList(
            $condition,
            "$tblBooking.calendar_staff_id"
        );

        $input['booked_calendar_id'] = $bookings->pluck('calendar_staff_id')->toArray();
        $calendarStaffList = $this->getCalendarStaffList($input);
        if ($calendarStaffList->isEmpty()) {
            return responseArrError(JsonResponse::HTTP_BAD_REQUEST, [config('errorCodes.calendar_staff.empty')]);
        }

        $calendarStaffArr = $calendarStaffList->random()->toArray();
        $videoCallType = Arr::get($input, 'video_call_type', EnumVideoCallType::TYPE_PUBLIC);
        $note = Arr::get($input, 'note');

        $bookingInput = [
            'calendar_staff_id' => $calendarStaffArr['id'],
            'customer_id' => auth('sanctum')->id(),
            'status' => EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE,
            'customer_note' => $note,
            'customer_video_call_type' => $videoCallType,
        ];
        $bookingInput['room_id'] = Str::uuid();
        $this->bookingRepository->create($bookingInput);
    }

    /**
     * Update booking.
     *
     * @param array $input
     * @return array
     * @throws Throwable
     */
    public function updateBooking(array $input)
    {
        $customerId = auth('sanctum')->id();
        $calendarStaffId = null;
        $bookingStatusArr = [
            EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE,
        ];
        $tblBooking = Booking::getTableName();

        $condition = [
            'booking_id' => $input['old_booking_id'],
            'customer_id' => $customerId,
            'booking_status' => $bookingStatusArr,
        ];
        $oldBooking = $this->bookingRepository->getDetail($condition, [
            'reception_date',
            'reception_start_time',
            "$tblBooking.calendar_staff_id",
        ]);
        if ($oldBooking
            && $oldBooking->reception_date == $input['reception_date']
            && Carbon::parse($oldBooking->reception_start_time)->format('H:i') == $input['reception_time']) {
            $calendarStaffId = $oldBooking->calendar_staff_id;
        }

        $bookings = $this->bookingRepository->getBookedBookingList(
            $condition,
            "$tblBooking.calendar_staff_id"
        );

        $input['booked_calendar_id'] = $bookings->pluck('calendar_staff_id')->toArray();
        if (!$calendarStaffId) {
            $calendarStaffList = $this->getCalendarStaffList($input);
            if ($calendarStaffList->isEmpty()) {
                return responseArrError(JsonResponse::HTTP_BAD_REQUEST, [config('errorCodes.calendar_staff.empty')]);
            }

            $calendarStaffArr = $calendarStaffList->random()->toArray();
            $calendarStaffId = $calendarStaffArr['id'];
        }
        $videoCallType = Arr::get($input, 'video_call_type', EnumVideoCallType::TYPE_PUBLIC);
        $note = Arr::get($input, 'note');

        $bookingInput = [
            'calendar_staff_id' => $calendarStaffId,
            'customer_id' => $customerId,
            'customer_video_call_type' => $videoCallType,
            'customer_note' => $note,
        ];
        $this->bookingRepository->update($condition['booking_id'], $bookingInput);
    }

    /**
     * Get the reservation history of staff.
     *
     * @param array $input
     * @return LengthAwarePaginator
     */
    public function getReservationHistoryOfStaff(array $input)
    {
        $input['store_id'] = Auth::user()->store_id;
        $tblCustomer = Customer::getTableName();
        $tblStaff = Staff::getTableName();
        $tblBooking = Booking::getTableName();
        $columns = [
            "$tblBooking.id AS booking_id",
            DB::raw("CONCAT($tblCustomer.surname, $tblCustomer.name) AS customer_name"),
            "$tblCustomer.id AS customer_id",
            "$tblCustomer.phone AS customer_phone",
            "$tblStaff.id AS staff_id",
            "$tblStaff.name AS staff_name",
            'reception_date',
            'reception_start_time',
            'reception_end_time',
            "customer_note AS note",
            "customer_video_call_type",
            "$tblBooking.store_video_call_type",
            "$tblBooking.status",
        ];

        return $this->bookingRepository->getReservationHistoryOfStaff($input, $columns);
    }

    /**
     * Get the reservation history of staff.
     *
     * @param array $input
     * @return LengthAwarePaginator
     */
    public function getReservationHistoryOfCustomer(array $input)
    {
        $tblStore = Store::getTableName();
        $tblBooking = Booking::getTableName();
        $columns = [
            "$tblBooking.id AS booking_id",
            "$tblStore.id AS store_id",
            "$tblStore.avatar AS store_avatar",
            "$tblStore.name AS store_name",
            'reception_date',
            'reception_start_time',
            'reception_end_time',
            "$tblBooking.status",
            "$tblBooking.customer_video_call_type",
            "$tblBooking.final_video_call_type AS video_call_type",
        ];

        return $this->bookingRepository->getReservationHistoryOfCustomer($input, $columns);
    }

    /**
     * Get booking status filter.
     *
     * @param $customerId
     * @param $storeId
     * @param int $type
     * @return Builder[]|Collection
     */
    public function getBookingStatusFilter(array $input, $type)
    {
        $tblBooking = Booking::getTableName();
        $column = [
            "$tblBooking.id AS booking_id",
            "$tblBooking.status"
        ];
        if ($type == EnumInitType::TYPE_USER) {
            $bookings = $this->bookingRepository->getReservationHistoryOfCustomer($input, $column, false);
        } else {
            $bookings = $this->bookingRepository->getReservationHistoryOfStaff($input, $column, false);
        }
        $bookingStatusListArr = [
            EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE,
            EnumBookingStatus::STATUS_CONFIRM_VIDEO_CALL_TYPE,
            EnumBookingStatus::STATUS_PROCESSING,
            EnumBookingStatus::STATUS_COMPLETE,
            EnumBookingStatus::STATUS_CANCEL,
            EnumBookingStatus::STATUS_CANCEL_FORCE,
        ];
        $arrayStatus = [];
        foreach ($bookingStatusListArr as $status) {
            $arrayStatus[$status] = [
                'status' => $status,
                'quantity' => 0
            ];
        }
        return $this->countBookingFilter($bookings, $arrayStatus, 'status');
    }

    /**
     * Get video call type filter.
     *
     * @param array $input
     * @return Builder[]|Collection
     */
    public function getVideoCallTypeFilter($input)
    {
        $tblBooking = Booking::getTableName();
        $column = [
            "$tblBooking.id",
            DB::raw(
                "CASE WHEN store_video_call_type IS NULL THEN 3 ELSE store_video_call_type END
                as store_video_call_type"
            )
        ];
        $videoCallTypeList = $this->bookingRepository->getReservationHistoryOfStaff($input, $column, false);
        $videoCallTypeListArr = [
            EnumVideoCallType::TYPE_PUBLIC => [
                'video_call_type' => EnumVideoCallType::TYPE_PUBLIC,
                'quantity' => 0
            ],
            EnumVideoCallType::TYPE_PRIVATE => [
                'video_call_type' => EnumVideoCallType::TYPE_PRIVATE,
                'quantity' => 0
            ],
            EnumVideoCallType::TYPE_NOT_CONFIRM => [
                'video_call_type' => EnumVideoCallType::TYPE_NOT_CONFIRM,
                'quantity' => 0
            ]
        ];

        return $this->countBookingFilter($videoCallTypeList, $videoCallTypeListArr, 'store_video_call_type');
    }

    /**
     * Cancel booking.
     *
     * @param int $id
     * @return array
     * @throws Throwable
     */
    public function cancelBooking(int $id)
    {
        $statusListArr = [
            EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE,
            EnumBookingStatus::STATUS_CONFIRM_VIDEO_CALL_TYPE,
        ];
        $condition = [
            'customer_id' => auth('sanctum')->user()->id,
            'booking_id' => $id,
            'status_list' => $statusListArr
        ];
        $tblStaff = Staff::getTableName();
        $staff = $this->staffRepository->getDetailFromBooking(
            $condition,
            ["$tblStaff.name", "$tblStaff.email"]
        );
        if (!$staff) {
            return responseArrError(JsonResponse::HTTP_NOT_FOUND, [config('errorCodes.staff.not_found')]);
        }

        $result = $this->bookingRepository->cancelBooking($condition);
        if (!$result) {
            return responseArrError(JsonResponse::HTTP_NOT_ACCEPTABLE, [config('errorCodes.booking.not_found')]);
        }

        SendMailCancelBooking::dispatch([
            'email' => $staff->email,
            'name' => $staff->name,
        ]);

        return [];
    }

    /**
     * Confirm video call type.
     *
     * @param int $bookingId
     * @param int $storeVideoCallType
     * @return bool
     */
    public function confirmVideoCallType(int $bookingId, int $storeVideoCallType)
    {
        $tblBooking = Booking::getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStore = Store::getTableName();
        $tblCustomer = Customer::getTableName();
        $columns = [
            "$tblBooking.status",
            "$tblBooking.id",
            "$tblBooking.customer_video_call_type",
            "$tblCustomer.email as customer_email",
            "$tblCustomer.send_mail",
            'final_video_call_type',
            "$tblCalendarStaff.reception_start_time",
            "$tblCalendarStaff.reception_end_time",
            "$tblCalendarStaff.reception_date",
            "$tblStore.name as store_name",
        ];
        $condition = [
            'booking_id' => $bookingId
        ];
        $booking = $this->bookingRepository->getDetail($condition, $columns);
        if (!$booking || $booking->status != EnumBookingStatus::STATUS_PENDING_CONFIRM_VIDEO_CALL_TYPE) {
            return false;
        }
        $statusFinal = $storeVideoCallType == $booking->customer_video_call_type ?
            $storeVideoCallType :
            EnumVideoCallType::TYPE_PRIVATE;
        $bookingTemp = $booking->toArray();
        if ($statusFinal != $booking->customer_video_call_type
            && $booking->send_mail
        ) {
            JobSendMailNotifyBookingPrivate::dispatch($bookingTemp);
        }
        $booking->update([
            'status' => EnumBookingStatus::STATUS_CONFIRM_VIDEO_CALL_TYPE,
            'store_video_call_type' => $storeVideoCallType,
            'final_video_call_type' => $statusFinal
        ]);
        return true;
    }

    /**
     * Mark user has joined video call.
     *
     * @param array $condition
     * @param array $dataAgora
     * @return array
     * @throws Exception
     */
    public function joinVideoCall(array $condition, $dataUpdate = [])
    {
        $tblBooking = Booking::getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStore = Store::getTableName();
        $tblCustomer = Customer::getTableName();
        $dataAgora = [];
        $columns = [
            "$tblBooking.id AS id",
            "$tblBooking.calendar_staff_id",
            "$tblBooking.id AS booking_customer_id",
            "$tblBooking.status",
            "$tblBooking.token",
            "$tblBooking.channel_name",
            "$tblBooking.room_id",
            "$tblBooking.start_time_actual",
            "$tblBooking.customer_id",
            "$tblCustomer.name as customer_name",
            "$tblCustomer.avatar as avatar_customer",
            'final_video_call_type',
            "$tblCalendarStaff.reception_start_time",
            "$tblCalendarStaff.reception_end_time",
            "$tblCalendarStaff.reception_date",
            "$tblStore.name as store_name",
            "$tblStore.avatar as avatar_store"
        ];
        $booking = $this->bookingRepository->getDetail($condition, $columns);
        if (!$booking) {
            return [];
        }
        if (($booking->status != EnumBookingStatus::STATUS_CONFIRM_VIDEO_CALL_TYPE
                && $booking->status != EnumBookingStatus::STATUS_PROCESSING)
            || ("$booking->reception_date $booking->reception_end_time" < now()->format('Y-m-d H:i:s')
                && $booking->status != EnumBookingStatus::STATUS_PROCESSING)
            || "$booking->reception_date $booking->reception_start_time" > now()->addMinutes(
                self::PREVIOUS_TIME_START
            )->format('Y-m-d H:i:s')) {
            $booking->is_call_video = false;
        } else {
            if ($booking->status != EnumBookingStatus::STATUS_PROCESSING) {
                $dataUpdate['status'] = EnumBookingStatus::STATUS_PROCESSING;
                $dataUpdate['start_time_actual'] = now()->format('Y-m-d H:i:s');
                $booking->update($dataUpdate);
            }
            $booking->is_call_video = true;
            $calendarStaff = $this->calendarStaffRepository->find($booking->calendar_staff_id);
            $dataAgora = [
                'is_public' => $booking->final_video_call_type,
                'token' => $booking->token,
                'channel_name' => $booking->channel_name,
                'room_id' => $booking->room_id,
                'user' => $this->customerRepository->getInfoCustomer($booking->customer_id, [
                    'id',
                    'name',
                    'surname',
                    'email'
                ])->toArray(),
                'staff' => $this->staffRepository->getInfoStaff($calendarStaff->staff_id, [
                    'id',
                    'name',
                    'email'
                ])->toArray(),
            ];
        }
        $timeRemain = 0;
        $timePassed = 0;
        if ($booking->start_time_actual && $booking->status == EnumBookingStatus::STATUS_PROCESSING) {
            $endTime = $booking->reception_date . ' ' . $booking->reception_end_time;
            $startTime = $booking->start_time_actual;
            $now = now()->format('Y-m-d H:i:s');
            $timeRemain = strtotime($endTime) - strtotime($now) + EnumBookingStatus::TIME_EXTENSION;
            $timePassed = strtotime($now) - strtotime($startTime);
        }
        $booking->time_remain = $timeRemain;
        $booking->time_passed = $timePassed;
        return [
            'booking' => $booking,
            'data_agora' => $dataAgora
        ];
    }

    /**
     * Get chat history.
     *
     * @param int $calendarStaffId
     * @return Builder[]|Collection
     */
    public function getChatHistory(int $calendarStaffId)
    {
        return $this->videoChatRepository->getChatHistory($calendarStaffId);
    }

    /**
     * Add chat comment.
     *
     * @param array $input
     * @param int $type
     * @return mixed
     */
    public function addChatComment(array $input, int $type)
    {
        $input['type'] = $type;

        return $this->videoChatRepository->create($input);
    }

    /**
     * Delete chat comment.
     *
     * @param int $id
     * @return mixed
     */
    public function deleteChatComment(int $id)
    {
        $this->videoChatRepository->delete($id);
    }

    /**
     * Get calendar staff list.
     *
     * @param $input
     * @return Builder[]|Collection
     */
    private function getCalendarStaffList($input)
    {
        $condition = Arr::only($input, [
            'store_id',
            'reception_date',
            'reception_time',
            'booked_calendar_id',
        ]);

        return $this->calendarStaffRepository->getListByReceptionDateTime(
            $condition,
            CalendarStaff::getTableName() . '.id'
        );
    }

    /**
     * Get filter.
     *
     * @param $typeList
     * @param array $typeListArr
     * @param string $typeField
     * @return array
     */
    private function countBookingFilter($bookings, array $arrayStatus, string $typeField)
    {
        foreach ($bookings as $booking) {
            $arrayStatus[$booking->$typeField]['quantity']++;
        }
        return array_values($arrayStatus);
    }

    public function countBookingType(array $bookingTypes)
    {
        $arrayBookingType = [
            EnumVideoCallType::TYPE_PUBLIC,
            EnumVideoCallType::TYPE_PRIVATE
        ];
        if (count($arrayBookingType) == count($bookingTypes)) {
            return $bookingTypes;
        }
        $bookingPublic = 0;
        $bookingPrivate = 0;
        if ($bookingTypes) {
            if ($bookingTypes[0]["video_call_type"] == EnumVideoCallType::TYPE_PUBLIC) {
                $bookingPublic = $bookingTypes[0]['quantity'];
            } else {
                $bookingPrivate = $bookingTypes[0]['quantity'];
            }
        }
        return [
            [
                'video_call_type' => EnumVideoCallType::TYPE_PUBLIC,
                'quantity' => $bookingPublic
            ],
            [
                'video_call_type' => EnumVideoCallType::TYPE_PRIVATE,
                'quantity' => $bookingPrivate
            ]
        ];
    }

    public function getListVideoHomePage(array $request)
    {
        return $this->bookingRepository->getListVideoHomePage($request);
    }

    public function getVideoCallTypeCustomerFilterSiteUser($input)
    {
        $tblBooking = Booking::getTableName();
        $column = [
            "$tblBooking.id AS booking_id",
            "$tblBooking.customer_video_call_type"
        ];
        $bookings = $this->bookingRepository->getReservationHistoryOfCustomer($input, $column, false);

        $arrayStatus = [
            EnumVideoCallType::TYPE_PUBLIC => [
                'video_call_type' => EnumVideoCallType::TYPE_PUBLIC,
                'quantity' => 0
            ],
            EnumVideoCallType::TYPE_PRIVATE => [
                'video_call_type' => EnumVideoCallType::TYPE_PRIVATE,
                'quantity' => 0
            ]
        ];
        return $this->countBookingFilter($bookings, $arrayStatus, 'customer_video_call_type');
    }

    public function getVideoCallTypeCustomerFilterSiteStaff($input)
    {
        $tblBooking = Booking::getTableName();
        $column = [
            "$tblBooking.id AS booking_id",
            "$tblBooking.customer_video_call_type"
        ];
        $bookings = $this->bookingRepository->getReservationHistoryOfStaff($input, $column, false);

        $arrayStatus = [
            EnumVideoCallType::TYPE_PUBLIC => [
                'video_call_type' => EnumVideoCallType::TYPE_PUBLIC,
                'quantity' => 0
            ],
            EnumVideoCallType::TYPE_PRIVATE => [
                'video_call_type' => EnumVideoCallType::TYPE_PRIVATE,
                'quantity' => 0
            ]
        ];
        return $this->countBookingFilter($bookings, $arrayStatus, 'customer_video_call_type');
    }

    public function endVideoCall($bookingId)
    {
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblBooking = Booking::getTableName();
        $condition = [
            'booking_id' => $bookingId
        ];
        $columns = [
            "$tblCalendarStaff.reception_end_time",
            "$tblCalendarStaff.reception_date",
            "$tblBooking.*"
        ];
        $booking = $this->bookingRepository->getDetail($condition, $columns);
        if ($booking
            && $booking->status == EnumBookingStatus::STATUS_PROCESSING
            && "$booking->reception_date $booking->reception_end_time" < now()->format('Y-m-d H:i:s')
        ) {
            return $booking->update([
                'status' => EnumBookingStatus::STATUS_COMPLETE,
                'end_time_actual' => now()->format('Y-m-d H:i:s')
            ]);
        }
        return false;
    }

    public function userJoinCall($bookingId)
    {
        $tblBooking = Booking::getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStore = Store::getTableName();
        $tblCustomer = Customer::getTableName();
        $columns = [
            "$tblBooking.id AS id",
            "$tblBooking.calendar_staff_id",
            "$tblBooking.id AS booking_customer_id",
            "$tblBooking.status",
            "$tblBooking.room_id",
            "$tblBooking.start_time_actual",
            "$tblBooking.customer_id",
            "$tblCustomer.name as customer_name",
            "$tblCustomer.avatar as avatar_customer",
            'final_video_call_type',
            "$tblCalendarStaff.reception_start_time",
            "$tblCalendarStaff.reception_end_time",
            "$tblCalendarStaff.reception_date",
            "$tblStore.name as store_name",
            "$tblStore.avatar as avatar_store"
        ];
        $booking = $this->bookingRepository->getDetail([
            'booking_id' => $bookingId
        ], $columns);
        $timeRemain = 0;
        $timePassed = 0;
        if ($booking->start_time_actual && $booking->status == EnumBookingStatus::STATUS_PROCESSING) {
            $endTime = $booking->reception_date . ' ' . $booking->reception_end_time;
            $startTime = $booking->start_time_actual;
            $now = now()->format('Y-m-d H:i:s');
            $timeRemain = strtotime($endTime) - strtotime($now) + EnumBookingStatus::TIME_EXTENSION;
            $timePassed = strtotime($now) - strtotime($startTime);
        }
        $booking->time_remain = $timeRemain;
        $booking->time_passed = $timePassed;
        return $booking;
    }
}
