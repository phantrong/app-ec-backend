<?php

namespace App\Repositories\LiveStream;

use App\Enums\EnumLiveStream;
use App\Enums\EnumProduct;
use App\Models\LiveStream;
use App\Models\Province;
use App\Models\Staff;
use App\Models\Store;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LiveStreamRepository extends BaseRepository implements LiveStreamRepositoryInterface
{
    const PER_PAGE_LIST = 10;
    const PER_PAGE_HOME_PAGE = 5;

    public function getModel(): string
    {
        return LiveStream::class;
    }

    private function getBuilderSchedule(array $request, $storeId = null, $isCMS = false)
    {
        $staff = Auth::user();
        $staffId = $staff->id;
        $isOwner = $staff->is_owner;
        $status = $request['status'] ?? null;
        $startDate = null;
        $endDate = null;
        $keyWord = null;
        $tableStore = Store::getTableName();
        $tableLiveStream = LiveStream::getTableName();
        if ($isCMS) {
            $startDate = $request['start_date'] ?? null;
            $endDate = $request['end_date'] ?? null;
            $keyWord = $request['key_word'] ?? null;
        }
        $statusEnd = EnumLiveStream::STATUS_END;
        $statusCancel = EnumLiveStream::STATUS_CANCEL;
        $tblLivestream = LiveStream::getTableName();
        return $this->model
            ->select(
                "$tableLiveStream.id",
                "$tableLiveStream.title",
                "$tableLiveStream.staff_id",
                "$tableLiveStream.image",
                "$tableLiveStream.start_time",
                "$tableLiveStream.status",
                "$tableLiveStream.violation",
                DB::raw(
                    "CASE WHEN $tblLivestream.status = $statusEnd OR
                    $tblLivestream.status = $statusCancel
                     THEN TIMESTAMPDIFF(second, start_time, now())
                    ELSE TIMESTAMPDIFF(second, now(), start_time) END as time"
                )
            )
            ->join($tableStore, "$tableStore.id", '=', "$tableLiveStream.store_id")
            ->when($staff instanceof Staff, function ($query) use ($staffId, $isOwner) {
                return $query->addSelect(
                    DB::raw("CASE WHEN staff_id = $staffId OR $isOwner THEN 1 ELSE 0 END as is_owner")
                );
            })
            ->when($status !== null, function ($query) use ($status, $tblLivestream) {
                return $query->where("$tblLivestream.status", $status);
            })
            ->when($startDate !== null, function ($query) use ($startDate) {
                return $query->whereDate('start_time', '>=', $startDate);
            })
            ->when($endDate !== null, function ($query) use ($endDate) {
                return $query->whereDate('start_time', '<=', $endDate);
            })
            ->when($keyWord !== null, function ($query) use ($keyWord, $tableLiveStream, $tableStore) {
                $keyWord = '%' . $keyWord . '%';
                return $query->where(function ($query) use ($keyWord, $tableLiveStream, $tableStore) {
                    $query->where("$tableLiveStream.title", 'like', $keyWord)
                    ->orWhere("$tableStore.name", 'like', $keyWord);
                });
            })
            ->when($storeId, function ($query) use ($storeId) {
                return $query->where('store_id', $storeId);
            })
            ->with(['staff:id,name'])
            ->orderby("$tblLivestream.status")
            ->orderby('time');
    }

    public function getListScheduleCMS(array $request)
    {
        $status = $request['status'] ?? null;
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;
        $keyWord = $request['key_word'] ?? null;
        $perPage = $request['per_page'] ?? self::PER_PAGE_LIST;
        $tableStore = Store::getTableName();
        $tableLiveStream = LiveStream::getTableName();
        $statusEnd = EnumLiveStream::STATUS_END;
        $statusCancel = EnumLiveStream::STATUS_CANCEL;
        $statusDisplay = [EnumLiveStream::STATUS_PLAYING, EnumLiveStream::STATUS_END];
        $now = now()->format('Y-m-d H:i:s');
        return $this->model
            ->select(
                "$tableLiveStream.id",
                'title',
                "$tableStore.name",
                "$tableStore.avatar",
                'image',
                'start_time',
                'violation',
                "$tableLiveStream.status",
                "$tableLiveStream.start_time_actual",
                "$tableLiveStream.end_time_actual",
                DB::raw("CASE WHEN url_video IS NOT NULL THEN 1 ELSE 0 END AS has_link"),
                'staff_id',
                'store_id',
                DB::raw(
                    "CASE WHEN $tableLiveStream.status = $statusEnd OR
                    $tableLiveStream.status = $statusCancel
                     THEN TIMESTAMPDIFF(second, start_time, '$now')
                    ELSE TIMESTAMPDIFF(second, '$now', start_time) END as time,
                    CASE WHEN start_time_actual is not null THEN TIMESTAMPDIFF(second, start_time_actual,'$now')
                       END as time_passed"
                ),
                DB::raw("CASE WHEN view IS NULL THEN 0 else view END as view")
            )
            ->join($tableStore, "$tableStore.id", '=', "$tableLiveStream.store_id")
            ->whereIn("$tableLiveStream.status", $statusDisplay)
            ->when($status !== null, function ($query) use ($status, $tableLiveStream) {
                return $query->where("$tableLiveStream.status", $status);
            })
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('start_time', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('start_time', '<=', $endDate);
            })
            ->when($keyWord, function ($query) use ($keyWord, $tableLiveStream, $tableStore) {
                $keyWord = '%' . $keyWord . '%';
                return $query->where(function ($query) use ($keyWord, $tableLiveStream, $tableStore) {
                    $query->where("$tableLiveStream.title", 'like', $keyWord)
                    ->orWhere("$tableStore.name", 'like', $keyWord);
                });
            })
            ->with([
                'staff:id,name,store_id',
                'store:id,name,avatar'
            ])
            ->orderby("$tableLiveStream.status")
            ->orderby('time')
            ->paginate($perPage);
    }

    public function getListSchedule(array $request, $storeId = null)
    {
        $perPage = $request['per_page'] ?? self::PER_PAGE_LIST;
        return $this->getBuilderSchedule($request, $storeId)->paginate($perPage);
    }

    public function getListAllSchedule(array $request, $storeId = null, $isCMS = false)
    {
        return $this->getBuilderSchedule($request, $storeId, $isCMS)->get();
    }

    public function getListLiveStreamHomePage($request)
    {
        $perPage = $request['per_page'] ?? self::PER_PAGE_HOME_PAGE;
        $status = $request['status'] ?? null;
        $storeId = $request['store_id'] ?? null;
        $keyWord = $request['key_word'] ?? null;
        $sort = $request['sort'] ?? null;
        $tableLiveStream = $this->model->getTableName();
        $statusProcessing = EnumLiveStream::STATUS_PLAYING;
        $statusEnd = EnumLiveStream::STATUS_END;
        $statusCancel = EnumLiveStream::STATUS_CANCEL;
        $now = now()->format('Y-m-d H:i:s');
        return $this->model
            ->select(
                "$tableLiveStream.id",
                "$tableLiveStream.title",
                "$tableLiveStream.staff_id",
                "$tableLiveStream.image",
                "$tableLiveStream.start_time_actual",
                "$tableLiveStream.end_time_actual",
                "$tableLiveStream.status",
                "$tableLiveStream.store_id",
                DB::raw(
                    "CASE WHEN $tableLiveStream.status = $statusEnd OR
                    $tableLiveStream.status = $statusCancel
                     THEN TIMESTAMPDIFF(second, start_time, '$now')
                    ELSE TIMESTAMPDIFF(second, '$now', start_time) END as time,
                CASE WHEN $tableLiveStream.status = $statusEnd OR $tableLiveStream.status = $statusProcessing
                THEN start_time_actual ELSE start_time END as start_time,
                CASE WHEN start_time_actual is not null THEN TIMESTAMPDIFF(second, start_time_actual, '$now')
                       END as time_passed"
                ),
                DB::raw(
                    "CASE WHEN view IS NULL THEN 0 else view END as view,
                 CASE WHEN url_video IS NOT NULL AND $tableLiveStream.status = $statusEnd
                 THEN 1 ELSE 0 END AS has_video"
                )
            )
            ->whereNull("$tableLiveStream.violation")
            ->when(is_array($status) && $status, function ($query) use ($status, $tableLiveStream) {
                return $query->whereIn("$tableLiveStream.status", $status);
            })
            ->when(
                $status && in_array(EnumLiveStream::STATUS_END, $status),
                function ($query) {
                    $statusEnd = EnumLiveStream::STATUS_END;
                    return $query->whereRaw("CASE WHEN status = $statusEnd THEN url_video IS NOT NULL ELSE true END");
                }
            )
            ->when($storeId, function ($query) use ($storeId, $tableLiveStream) {
                return $query->where("$tableLiveStream.store_id", $storeId);
            })
            ->with([
                'staff:id,name,store_id',
                'store:id,name,avatar'
            ])
            ->when($keyWord, function ($query) use ($keyWord, $tableLiveStream) {
                $keyWord = '%' . $keyWord . '%';
                return $query->where("$tableLiveStream.title", 'like', $keyWord);
            })
            ->when($sort, function ($query) use ($sort, $tableLiveStream) {
                switch ($sort) {
                    case EnumLiveStream::SORT_BY_OLD:
                        return $query->orderby("$tableLiveStream.status")
                        ->orderbyDesc('time');
                    case EnumLiveStream::SORT_BY_VIEW:
                        return $query->orderByDesc("$tableLiveStream.view");
                    default:
                        return $query->orderby("$tableLiveStream.status")
                            ->orderby('time');
                }
            })
            ->when(!$sort, function ($query) use ($tableLiveStream) {
                return $query->orderby("$tableLiveStream.status")
                    ->orderby('time');
            })
            ->paginate($perPage);
    }

    public function getDetailLivestream($livestreamId)
    {
        $statusEnd = EnumLiveStream::STATUS_END;
        return $this->model
            ->select(
                'id',
                'room_id',
                'title',
                'status',
                'start_time',
                'start_time_actual',
                'staff_id',
                'store_id',
                DB::raw(
                    "CASE WHEN view IS NULL THEN 0 else view END as view,
                    CASE WHEN url_video IS NOT NULL AND status = $statusEnd THEN url_video ELSE null END AS url_video"
                ),
            )
            ->where('id', $livestreamId)
            ->with([
                'staff:id,name',
                'store' => function ($query) {
                    $tblStore = Store::getTableName();
                    $tblProvince = Province::getTableName();
                    return $query->select(
                        "$tblStore.id",
                        "$tblStore.name",
                        'phone',
                        'avatar',
                        'work_day',
                        'time_start',
                        'time_end',
                        DB::raw("CONCAT($tblProvince.name,city,place,COALESCE(address,'')) as address")
                    )
                        ->join($tblProvince, 'province_id', "$tblProvince.id");
                }
            ])
            ->first();
    }

    public function getTokenLivestream($livestreamId)
    {
        return $this->model
            ->select(
                'id',
                'status',
                'start_time',
                'token',
                'channel_name'
            )
            ->where('id', $livestreamId)
            ->where('status', EnumLiveStream::STATUS_PLAYING)
            ->whereNull('violation')
            ->first();
    }

    public function checkStaffIsLivestream($staffId)
    {
        return $this->model
            ->where('status', EnumLiveStream::STATUS_PLAYING)
            ->where('staff_id', $staffId)
            ->exists();
    }

    public function deleteSchedule($livestreamId)
    {
        return $this->model
            ->where('id', $livestreamId)
            ->where('status', '<>', EnumLivestream::STATUS_PLAYING)
            ->delete();
    }

    public function checkCalendarLivestream($staffId)
    {
        return $this->model
            ->where('start_time', '>=', now()->format('Y-m-d H:i:s'))
            ->where('staff_id', $staffId)
            ->exists();
    }

    public function checkLivestreamRecorded($livestreamId)
    {
        return $this->model
            ->where('id', $livestreamId)
            ->whereNotNull('start_id_cloud')
            ->exists();
    }
}
