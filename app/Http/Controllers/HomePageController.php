<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Services\BookingService;
use App\Services\LiveStreamService;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomePageController extends BaseController
{
    private LiveStreamService $liveStreamService;
    private BookingService $bookingService;
    private StoreService $storeService;

    public function __construct(
        LiveStreamService $liveStreamService,
        BookingService $bookingService,
        StoreService $storeService
    ) {
        $this->liveStreamService = $liveStreamService;
        $this->bookingService = $bookingService;
        $this->storeService = $storeService;
    }

    public function getListLiveStreamHomePage(Request $request): JsonResponse
    {
        try {
            $liveStreams = $this->liveStreamService->getListLiveStreamHomePage($request);
            return $this->sendResponse($liveStreams);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getListVideoHomePage(Request $request): JsonResponse
    {
        try {
            $videos = $this->bookingService->getListVideoHomePage($request->all());
            return $this->sendResponse($videos);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getListInstagram(Request $request): JsonResponse
    {
        try {
            $links = $this->storeService->getListInstagram($request->all());
            return $this->sendResponse($links);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
