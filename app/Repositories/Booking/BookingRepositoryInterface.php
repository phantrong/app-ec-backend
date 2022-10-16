<?php

namespace App\Repositories\Booking;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BookingRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * Get booked booking list.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getBookedBookingList(array $condition, $columns = ['*']);

    /**
     * Get reservation history.
     *
     * @param array $input
     * @param array $columns
     * @param int $paginate
     * @return LengthAwarePaginator|Collection
     */
    public function getReservationHistoryOfStaff(array $input, $columns = ['*'], $paginate = true);

    /**
     * Get reservation history of customer.
     *
     * @param array $input
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getReservationHistoryOfCustomer(array $input, $columns = ['*']);

    /**
     * Get video call type of end user has quantity.
     *
     * @param $customerId
     * @param $storeId
     * @return Builder[]|Collection
     */
    public function getVideoCallTypeEndUserHasQuantity($customerId = null, $storeId = null);

    /**
     * Get detail booking.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder|Model
     */
    public function getDetail(array $condition, $columns = ['*']);

    /**
     * Get booking detail by condition.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getDetailByCondition(array $condition, $columns = ['*']);

    /**
     * Cancel booking.
     *
     * @param array $condition
     * @return int
     */
    public function cancelBooking(array $condition);

    /**
     * get list video playing.
     *
     * @param array $request
     * @return LengthAwarePaginator
     */
    public function getListVideoHomePage(array $request);

    /**
     * Update store video call type.
     *
     * @param array $condition
     * @param array $values
     * @return int
     */
    public function updateVideoCallType(array $condition, array $values);

    /**
     * check staff calling video
     *
     * @param int $staffId
     * @return bool
     */
    public function checkStaffIsCallVideo($staffId);

    /**
     * update booking complete when end time
     *
     * @return bool
     */
    public function updateVideoComplete();

    /**
     * update actual end time video when shop not end
     *
     * @return bool
     */
    public function updateActualTimeVideo();
}
