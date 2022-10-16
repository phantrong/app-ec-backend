<?php

namespace App\Http\Controllers\Api;

use App\Services\ProvinceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvinceController extends BaseController
{
    private ProvinceService $provinceService;

    public function __construct(ProvinceService $provinceService)
    {
        $this->provinceService = $provinceService;
    }

    public function getAll()
    {
        try {
            return $this->sendResponse($this->provinceService->getAll());
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function countStoreByFilter(Request $request): JsonResponse
    {
        try {
            $provinces = $this->provinceService->countStoreByFilter($request);
            return $this->sendResponse($provinces);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
