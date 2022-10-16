<?php

namespace App\Repositories\CalendarStaff;

use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface CalendarStaffRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * Get list of calendar.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getList(array $condition, $columns = ['*']);

    /**
     * Get calendar list by condition.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getListByCondition(array $condition, $columns = ['*']);

    /**
     * Get calendar_staff list by reception date and reception start time.
     *
     * @param array $input
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getListByReceptionDateTime(array $input, $columns = ['*']);

    /**
     * Get calendar_staff list which have not booked reception time by staff id and reception date.
     *
     * @param array $condition
     * @param array $columns
     * @return object
     */
    public function getListActiveByStaffId(array $condition, $columns = ['*']);

    /**
     * Get calendar of staff.
     *
     * @param array $input reception date and staff id
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getCalendarOfStaff(array $input, $columns = ['*']);

    /**
     * Get booked calendar.
     *
     * @param array $input reception date and staff id
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getBookedCalendarList(array $input, $columns = ['*']);

    /**
     * Get booked calendar detail.
     *
     * @param int $id
     * @param array $columns
     * @return Builder|Model|object|null
     */
    public function getBookedCalendarDetail(int $id, $columns = ['*']);

    /**
     * Update or create calendar_staff.
     *
     * @param int $id
     * @param array $values
     * @return Builder|Model
     */
    public function updateOrCreate(int $id, array $values);

    /**
     * Delete multi calendar_staff.
     *
     * @param array $ids
     * @return mixed
     */
    public function deleteMulti(array $ids);

    /**
     * check staff has calendar
     *
     * @param int $staffId
     * @return bool
     */
    public function checkStaffHasCalendar($staffId);
}
