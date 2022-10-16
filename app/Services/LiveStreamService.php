<?php

namespace App\Services;

use App\Enums\EnumLiveStream;
use App\Models\Staff;
use App\Repositories\LiveStream\LiveStreamRepository;
use App\Repositories\LiveStreamMongo\LiveStreamMongoRepository;
use App\Repositories\Product\ProductRepository;
use Illuminate\Support\Facades\Auth;

class LiveStreamService
{
    private LiveStreamRepository $liveStreamRepository;
    private LiveStreamMongoRepository $liveStreamMongoRepository;
    private ProductRepository $productRepository;

    public function __construct(
        LiveStreamRepository $liveStreamRepository,
        LiveStreamMongoRepository $liveStreamMongoRepository,
        ProductRepository $productRepository
    ) {
        $this->liveStreamRepository = $liveStreamRepository;
        $this->liveStreamMongoRepository = $liveStreamMongoRepository;
        $this->productRepository = $productRepository;
    }

    public function createSchedule($data)
    {
        return $this->liveStreamRepository->create($data);
    }

    public function updateSchedule($liveStreamId, array $data)
    {
        return $this->liveStreamRepository->update($liveStreamId, $data);
    }

    public function getListSchedule(array $request, $storeId = null)
    {
        $schedules = $this->liveStreamRepository->getListSchedule($request, $storeId);
        $arrayStatus = $this->countScheduleByStatus([], $storeId);
        return [
            'schedules' => $schedules,
            'status' => $arrayStatus
        ];
    }

    public function countScheduleByStatus($request, $storeId = null, $isCMS = false)
    {
        if ($request && isset($request['status'])) {
            unset($request['status']);
        }
        $schedules = $this->liveStreamRepository->getListAllSchedule($request, $storeId, $isCMS);
        $arrayStatus = EnumLiveStream::ARRAY_STATUS;
        $countStatus = [];
        foreach ($arrayStatus as $status) {
            $countStatus[] = [
                'status' => $status,
                'total_schedule' => 0
            ];
        }

        foreach ($schedules as $schedule) {
            $countStatus[$schedule->status]['total_schedule']++;
        }
        return $countStatus;
    }

    public function deleteSchedule($liveStreamId)
    {
        return $this->liveStreamRepository->deleteSchedule($liveStreamId);
    }

    public function getListScheduleCMS(array $request)
    {
        $isCMS = true;
        $schedules = $this->liveStreamRepository->getListScheduleCMS($request);
        $arrayStatus = $this->countScheduleByStatus($request, null, $isCMS);
        return [
            'schedules' => $schedules,
            'status' => $arrayStatus
        ];
    }

    public function getListLiveStreamHomePage($request)
    {
        return $this->liveStreamRepository->getListLiveStreamHomePage($request);
    }

    public function createLivestreamMongo($livestreamId)
    {
        return $this->liveStreamMongoRepository->createLivestream($livestreamId);
    }

    public function getLivestreamById($livestreamId)
    {
        return $this->liveStreamRepository->find($livestreamId);
    }

    public function getDetailLivestream($livestreamId)
    {
        $livestream = $this->liveStreamRepository->getDetailLivestream($livestreamId);
        $productStocking = $this->productRepository->getProductStockingByStore($livestream->store_id);
        $livestream->store->products_count = $productStocking;
        return $livestream;
    }

    public function getTokenlivestream($livestreamId)
    {
        return $this->liveStreamRepository->getTokenLivestream($livestreamId);
    }

    public function checkStaffIsLivestream($staffId)
    {
        return $this->liveStreamRepository->checkStaffIsLivestream($staffId);
    }

    public function checkLivestreamRecorded($livestreamId)
    {
        return $this->liveStreamRepository->checkLivestreamRecorded($livestreamId);
    }
}
