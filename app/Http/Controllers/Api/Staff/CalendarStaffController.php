<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\EnumInitType;
use App\Enums\EnumVideoChatType;
use App\Events\ChatEvent;
use App\Events\Livestream;
use App\Events\VideoCall;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\CalendarStaffDetailRequest;
use App\Http\Requests\CalendarStaffRequest;
use App\Http\Requests\ConfirmVideoCallTypeRequest;
use App\Http\Requests\EditCalendarStaffRequest;
use App\Services\AgoraService;
use App\Services\BookingService;
use App\Services\CalendarStaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CalendarStaffController extends BaseController
{
    private CalendarStaffService $calendarStaffService;
    private BookingService $bookingService;
    private AgoraService $agoraService;

    public function __construct(
        CalendarStaffService $calendarStaffService,
        BookingService $bookingService,
        AgoraService $agoraService
    ) {
        $this->calendarStaffService = $calendarStaffService;
        $this->bookingService = $bookingService;
        $this->agoraService = $agoraService;
    }

    /**
     * Get calendar list.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getCalendarList(Request $request)
    {
        try {
            $input = $request->only([
                'reception_start_date',
                'reception_end_date'
            ]);
            $receptionStartDate = isset($input['reception_start_date']) ?
                date('Y-m-d', strtotime($input['reception_start_date'])) :
                now()->format('Y-m-d');
            $receptionEndDate = isset($input['reception_end_date']) ?
                date('Y-m-d', strtotime($input['reception_end_date'])) :
                now()->format('Y-m-d');
            if ($receptionStartDate > $receptionEndDate) {
                $bookings = collect();
            } else {
                $receptionDate = [
                    $receptionStartDate,
                    $receptionEndDate,
                ];

                $bookings = $this->calendarStaffService->getCalendarList($receptionDate);
            }

            return $this->sendResponse($bookings);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get booked calendar detail.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getBookedCalendarDetail(int $id)
    {
        try {
            $bookedCalendar = $this->calendarStaffService->getBookedCalendarDetail($id);
            if (!$bookedCalendar) {
                return $this->sendResponse([config('errorCodes.booking.not_found')], JsonResponse::HTTP_NOT_ACCEPTABLE);
            }
            return $this->sendResponse($bookedCalendar);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Add calendar for staff.
     *
     * @param  CalendarStaffRequest  $request
     * @return JsonResponse
     */
    public function addCalendar(CalendarStaffRequest $request)
    {
        try {
            $result = $this->calendarStaffService->addCalendar($request->all());
            $response = $this->sendResponse(null, JsonResponse::HTTP_OK, $result);
        } catch (\Exception $e) {
            $response = $this->sendError($e);
        }

        return $response;
    }

    /**
     * Get calendar of staff.
     *
     * @param CalendarStaffDetailRequest $request
     * @return JsonResponse
     */
    public function getCalendarOfStaff(CalendarStaffDetailRequest $request)
    {
        try {
            $data = $this->calendarStaffService->getCalendarOfStaff($request->all());
            if (!$data) {
                return $this->sendResponse(
                    [config('errorCodes.calendar_staff.not_found')],
                    JsonResponse::HTTP_NOT_ACCEPTABLE
                );
            }
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Edit calendar for staff.
     *
     * @param  EditCalendarStaffRequest  $request
     * @return JsonResponse|array
     */
    public function editCalendar(EditCalendarStaffRequest $request)
    {
        try {
            $data = $this->calendarStaffService->changeCalendar($request->all());
            if (isset($data['errorCode'])) {
                return $this->sendResponse($data['errorCode'], $data['status']);
            }
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get the reservation history.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getReservationHistory(Request $request)
    {
        try {
            $data = $this->bookingService->getReservationHistoryOfStaff($request->all());
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get booking status filter.
     *
     * @return JsonResponse
     */
    public function getBookingStatusFilter(Request $request)
    {
        try {
            $input = $request->except(['booking_status']);
            $input['store_id'] = auth('sanctum')->user()->store_id;
            $status = $this->bookingService->getBookingStatusFilter($input, EnumInitType::TYPE_SHOP);
            return $this->sendResponse($status);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get video call type filter.
     *
     * @return JsonResponse
     */
    public function getVideoCallTypeFilter(Request $request)
    {
        try {
            $input = $request->except(['booking_type']);
            $input['store_id'] = auth('sanctum')->user()->store_id;
            $data = $this->bookingService->getVideoCallTypeFilter($input);
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get video call type of user.
     *
     * @return JsonResponse
     */
    public function getVideoCallTypeCustomerFilter(Request $request)
    {
        try {
            $input = $request->except(['booking_type_customer']);
            $input['store_id'] = Auth::user()->store_id;
            $data = $this->bookingService->getVideoCallTypeCustomerFilterSiteStaff($input);
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Confirm video call type.
     *
     * @param int $bookingId
     * @param ConfirmVideoCallTypeRequest $request
     * @return JsonResponse
     */
    public function confirmVideoCallType(int $bookingId, ConfirmVideoCallTypeRequest $request)
    {
        try {
            $result = $this->bookingService->confirmVideoCallType($bookingId, $request->video_call_type);
            return $result ?
                $this->sendResponse() :
                $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Mark staff has joined video call.
     *
     * @param int $bookingId
     * @return JsonResponse
     */
    public function joinVideoCall(int $bookingId)
    {
        $condition = [
            'booking_id' => $bookingId,
            'store_id' => Auth::user()->store_id,
        ];
        try {
            $dataAgora['channel_name'] = Str::random(config('services.agora_channel_name_length'));
            $dataAgora['token'] = $this->agoraService->generateToken($dataAgora['channel_name']);
            $data = $this->bookingService->joinVideoCall($condition, $dataAgora);
            if (!$data) {
                return $this->sendResponse($data);
            }
            $booking = $data['booking']->toArray();
            if ($data['data_agora']) {
                event(new VideoCall($data['data_agora']));
            }
            $booking = Arr::except($booking, ['token', 'channel_name']);
            return  $this->sendResponse($booking);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get chat history.
     *
     * @param  int  $calendarStaffId
     * @return JsonResponse
     */
    public function getChatHistory(int $calendarStaffId)
    {
        try {
            $data = $this->bookingService->getChatHistory($calendarStaffId);
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Add chat comment.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function addChatComment(Request $request)
    {
        try {
            $type = EnumVideoChatType::TYPE_STAFF;
            $data = $this->bookingService->addChatComment($request->all(), $type);
            event(new ChatEvent($data));
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Delete chat comment.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function deleteChatComment(int $id)
    {
        try {
            $this->bookingService->deleteChatComment($id);
            event(new ChatEvent());
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function endVideoCall($bookingId)
    {
        try {
            $result = $this->bookingService->endVideoCall($bookingId);
            return $result ? $this->sendResponse() : $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
