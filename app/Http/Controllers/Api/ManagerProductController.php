<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnumFile;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\MarkViolationRequest;
use App\Services\CartItemService;
use App\Services\ProductMediaService;
use App\Services\ProductService;
use App\Services\ProductTypeConfigService;
use App\Services\ProductClassService;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerProductController extends BaseController
{
    private ProductService $productService;
    private UploadService $uploadService;
    private ProductMediaService $productMedia;
    private ProductTypeConfigService $productTypeConfigService;
    private ProductClassService $productClassService;
    private CartItemService $cartItemService;

    public function __construct(
        ProductService $productService,
        UploadService $uploadService,
        ProductMediaService $productMedia,
        ProductTypeConfigService $productTypeConfigService,
        ProductClassService $productClassService,
        CartItemService $cartItemService
    ) {
        $this->productService = $productService;
        $this->uploadService = $uploadService;
        $this->productMedia = $productMedia;
        $this->productTypeConfigService = $productTypeConfigService;
        $this->productClassService = $productClassService;
        $this->cartItemService = $cartItemService;
    }

    public function createProduct(CreateProductRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $storeId = $request->user()->store_id;
            $dataProduct = $request->only('name', 'category_id', 'brand_id', 'status', 'description', 'note');
            $dataProduct['store_id'] = $storeId;
            $dataProduct['property'] = $request->property;
            $product = $this->productService->createProduct($dataProduct);
            $images = $this->uploadService->uploadFile($request->image, EnumFile::IMAGE_PRODUCT, 'products');
            $this->productMedia->createProductMedia($images, $product);
            $isConfig = $request->is_config ?? null;
            if ($isConfig) {
                $dataProductType = $request->product_type;
                $this->productTypeConfigService->createProductTypeConfig($product->id, $dataProductType);
                $this->productClassService->createProductClassHasConfig($request->product_class, $product);
            } else {
                $dataProductClass = $request->only('price', 'stock', 'discount');
                $this->productClassService->createProductClassNotConfig($dataProductClass, $product);
            }
            DB::commit();
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function getInfoProduct($productId): JsonResponse
    {
        try {
            $product = $this->productService->getInfoProductConvert($productId);
            return $this->sendResponse($product);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getAllProduct(Request $request): JsonResponse
    {
        try {
            $storeId = $request->user()->store_id;
            $products = $this->productService->getAllProductByStore($request->all(), $storeId);
            return $this->sendResponse($products);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateProduct(CreateProductRequest $request, $productId): JsonResponse
    {
        DB::beginTransaction();
        try {
            $dataProduct = $request->only(
                'name',
                'category_id',
                'brand_id',
                'status',
                'description',
                'note',
                'property'
            );
            if ($dataProduct) {
                $product = $this->productService->updateProduct($dataProduct, $productId);
            }
            $isConfig = $this->productService->checkProductHasConfig($productId);
            //delete image
            if ($request->image_delete) {
                $this->productMedia->deleteProductMedia($request->image_delete);
                $this->uploadService->deleteListFile($request->image_delete);
            }
            if ($request->product_class_delete) {
                $this->productClassService->deleteProductClass($request->product_class_delete);
            }
            if ($request->product_type_delete) {
                $this->productTypeConfigService->deleteListConfig($request->product_type_delete);
            }
            //upload image new
            if ($request->image) {
                $images = $this->uploadService->uploadFile($request->image, EnumFile::IMAGE_PRODUCT, 'products');
                $this->productMedia->createProductMedia($images, $product);
            }
            // if have classify
            if ($request->is_config) {
                // if product hasn't classify
                if (!$isConfig) {
                    $this->productClassService->deleteProductClassNotConfig($productId);
                }
                $dataProductType = $request->product_type;
                if ($dataProductType) {
                    $this->productTypeConfigService->updateProductTypeConfig($productId, $dataProductType);
                }
                if ($request->product_class) {
                    $this->productClassService->updateProductClassHasConfig($request->product_class, $product);
                }
            } else {
                $dataProductClass = $request->only('price', 'stock', 'discount');
                if ($dataProduct) {
                    if ($isConfig) {
                        $this->productTypeConfigService->deleteProductTypeConfig($productId);
                        $this->productClassService->deleteProductClassByProduct($productId);
                        $this->productClassService->createProductClassNotConfig($dataProductClass, $product);
                    } else {
                        $this->productClassService->updateProductClassNotConfig($dataProductClass, $product->id);
                    }
                }
            }
            DB::commit();
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }


    public function deleteProduct($productId): JsonResponse
    {
        DB::beginTransaction();
        try {
            $errorCode = "false";
            $result = $this->productService->checkProductSold($productId);
            // if product sold
            if ($result) {
                $errorCode = config('errorCodes.product.sold');
            } else {
                $this->productService->deleteProduct($productId);
                $this->cartItemService->deleteCateItemByProduct($productId);
                DB::commit();
            }
            return $errorCode == "false" ? $this->sendResponse(null) :
                $this->sendResponse([$errorCode], JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    /**
     * Get list of products in CMS.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getList(Request $request): JsonResponse
    {
        try {
            $data = $this->productService->getListByStoreCMS($request->all());
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Mark violation product.
     *
     * @param int $id
     * @param MarkViolationRequest $request
     * @return JsonResponse
     */
    public function markViolation(int $id, MarkViolationRequest $request): JsonResponse
    {
        try {
            $data = $this->productService->markViolation($id, $request->all());
            if (isset($data['errorCode'])) {
                return $this->sendResponse($data['errorCode'], $data['status']);
            }
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Unmark violation product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function unmarkViolation(int $id): JsonResponse
    {
        try {
            $result = $this->productService->unmarkViolation($id);
            return $result ? $this->sendResponse() : $this->sendResponse(null, JsonResponse::HTTP_NOT_ACCEPTABLE) ;
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
