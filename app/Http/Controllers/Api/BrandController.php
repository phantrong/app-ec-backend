<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BrandRequest;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends BaseController
{
    private BrandService $brandService;

    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
    }

    public function getBrandProductCount(Request $request): JsonResponse
    {
        try {
            $brands = $this->brandService->getBrandProductCount($request->all());
            return $this->sendResponse($brands);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getAllBrand(Request $request): JsonResponse
    {
        try {
            $brands = $this->brandService->getAllBrand($request->all());
            return $this->sendResponse($brands);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function createBrand(BrandRequest $request)
    {
        try {
            $data = $request->only('name', 'category_id', 'status');
            $this->brandService->createBrand($data);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateBrand(BrandRequest $request, $brandId)
    {
        try {
            $data = $request->only('name', 'category_id', 'status');
            $this->brandService->updateBrand($brandId, $data);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function deleteBrand($brandId)
    {
        try {
            $errorCode = "false";
            $result = $this->brandService->checkBrandUsed($brandId);
            if ($result) {
                $errorCode = config('errorCodes.category.used');
            } else {
                $this->brandService->deleteBrand($brandId);
            }
            return $errorCode == "false" ? $this->sendResponse() :
                $this->sendResponse([$errorCode], JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
