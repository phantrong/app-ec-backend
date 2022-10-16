<?php

namespace App\Repositories\LiveStream;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LiveStreamRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * get all list live stream paginate
     *
     * @param  array  $request
     * @param  integer|null  $storeId
     * @return LengthAwarePaginator
     */
    public function getListSchedule(array $request, $storeId = null);

    /**
     * get all list live stream
     *
     * @param  array  $request
     * @param  integer|null  $storeId
     * @return Builder[]|Collection
     */
    public function getListAllSchedule(array $request, $storeId = null, $isCMS = false);

    /**
     * get all list livestream site CMS
     *
     * @param  array  $request
     * @return LengthAwarePaginator
     */
    public function getListScheduleCMS(array $request);

    /**
     * get detail live stream
     *
     * @param  int $livestreamId
     * @return object|null
     */
    public function getDetailLivestream($livestreamId);

    /**
     * get token live stream
     *
     * @param  int $livestreamId
     * @return object|null
     */
    public function getTokenLivestream($livestreamId);

    /**
     * check staff live streaming
     *
     * @param  int $staffId
     * @return bool
     */
    public function checkStaffIsLivestream($staffId);

    /**
     * check staff has calendar livestream
     *
     * @param  int $staffId
     * @return bool
     */
    public function checkCalendarLivestream($staffId);

    /**
     * check livestream recorded
     *
     * @param  int $livestreamId
     * @return bool
     */
    public function checkLivestreamRecorded($livestreamId);
}
