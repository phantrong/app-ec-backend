<?php

namespace App\Repositories\Staff;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface StaffRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * Get list of staff, relative query with name or email columns.
     *
     * @param array $condition
     * @param array $columns
     * @return LengthAwarePaginator|Collection
     */
    public function getListStaff(array $condition, $columns = ['*'], $paginate = true);

    /**
     * Get list of active staff.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function getListActiveStaff(array $condition, $columns = ['*']);

    /**
     * getStaffByEmail
     *
     * @param  string $email
     * @return object
     */
    public function getStaffByEmail($email);

    /**
     * Get detail from booking.
     *
     * @param array $condition
     * @param array $columns
     * @return Builder|Model|object
     */
    public function getDetailFromBooking(array $condition, $columns = ['*']);

    /**
     * Get owner list.
     *
     * @param array $storeIdArr
     * @param array $columns
     * @return Builder|Model|object
     */
    public function getOwnerList(array $storeIdArr, $columns = ['*']);

    /**
     * Get owner detail.
     *
     * @param int $storeId
     * @param array $columns
     * @return Builder|Model|object
     */
    public function getOwnerDetail(int $storeId, $columns = ['*']);

    /**
     * Delete staff in same store by id.
     *
     * @param int $id
     * @return mixed
     */
    public function deleteStaff(int $id);

    /**
     * change email staff by email
     *
     * @param string $oldEmail
     * @param string $newEmail
     * @return mixed
     */
    public function changeEmail($oldEmail, $newEmail);

    /**
     * Get most field of staff by id and field.
     *
     * @param int $staffId
     * @param array $fields
     * @return Builder|Model|object
     */
    public function getInfoStaff($staffId, $fields);
}
