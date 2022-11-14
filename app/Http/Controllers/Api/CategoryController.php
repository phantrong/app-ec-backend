<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnumFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Services\CategoryService;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CategoryController extends BaseController
{
    private CategoryService $categoryService;
    private UploadService $uploadService;

    public function __construct(CategoryService $categoryService, UploadService $uploadService)
    {
        $this->categoryService = $categoryService;
        $this->uploadService = $uploadService;
    }

    public function getCategoryProductCount(Request $request): JsonResponse
    {
        try {
            $categories = $this->categoryService->getCategoryProductCount($request->all());
            return $this->sendResponse($categories);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getAllCategory(Request $request): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAllCategory($request->all());
            return $this->sendResponse($categories);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getCategoryBestSale(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getCategoryBestSale();
            return $this->sendResponse($categories);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getCategoryByStore($storeId): JsonResponse
    {
        try {
            $categories = $this->categoryService->getCategoryByStore($storeId);
            return $this->sendResponse($categories);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getListCategoryCMS(Request $request)
    {
        try {
            $categories = $this->categoryService->getListCategoryCMS($request->all());
            return $this->sendResponse($categories);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function createCategory(CategoryRequest $request)
    {
        try {
            $image = $this->uploadService->uploadFileStorage($request->image);
            $data = $request->only('name', 'status');
            $data['image_path'] = env('APP_PATH') . $image;
            $this->categoryService->createCategory($data);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getInfoCategory(Request $request, $categoryId)
    {
        try {
            $brands = $this->categoryService->getInfoCategory($categoryId, $request->all());
            return $this->sendResponse($brands);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateCategory(CategoryRequest $request, $categoryId)
    {
        try {
            $data = $request->only('name', 'status');
            if ($request->image) {
                $image = $this->uploadService->uploadFileStorage($request->image);
                $data['image_path'] = env('APP_PATH') . $image;
            }
            $this->categoryService->updateCategory($categoryId, $data);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function deleteCategory($categoryId): JsonResponse
    {
        try {
            $errorCode = "false";
            $result = $this->categoryService->checkCategoryUsed($categoryId);
            if ($result) {
                $errorCode = config('errorCodes.category.used');
            } else {
                $this->categoryService->deleteCategory($categoryId);
            }
            return $errorCode == "false" ? $this->sendResponse() :
                $this->sendResponse([$errorCode], JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
