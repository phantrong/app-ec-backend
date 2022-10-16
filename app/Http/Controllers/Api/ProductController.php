<?php

namespace App\Http\Controllers\Api;

use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function searchProduct(Request $request): JsonResponse
    {
        try {
            $customerId = auth('sanctum')->user()->id ?? null;
            $products = $this->productService->searchProduct($request->all(), $customerId);
            return $this->sendResponse($products);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getProductBestSaleByCategory(Request $request): JsonResponse
    {
        try {
            $products = $this->productService->getProductBestSaleByCategory($request->all());
            return $this->sendResponse($products);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getProductDetail($id): JsonResponse
    {
        try {
            $customerId = auth('sanctum')->user()->id ?? null;
            $product = $this->productService->getDetailProduct($id, $customerId);
            return $this->sendResponse($product);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getProductReference($id): JsonResponse
    {
        try {
            $customerId = auth('sanctum')->user()->id ?? null;
            $product = $this->productService->getProductReference($id, $customerId);
            return $this->sendResponse($product);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getProductBestSaleByStore($storeId): JsonResponse
    {
        try {
            $customerId = auth('sanctum')->user()->id ?? null;
            $products = $this->productService->getProductBestSaleByStore($storeId, $customerId);
            return $this->sendResponse($products);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function likeProduct(Request $request): JsonResponse
    {
        try {
            $customerId = $request->user()->id;
            $productId = $request->product_id;
            $this->productService->likeProduct($customerId, $productId);
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getProductFavorite(Request $request)
    {
        try {
            $customerId = $request->user()->id;
            $sort = $request->only('sort');
            $products = $this->productService->getProductFavorite($sort, $customerId);
            return $this->sendResponse($products);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
