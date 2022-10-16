<?php

namespace App\Services;

use App\Enums\EnumProduct;
use App\Jobs\SendMailMarkViolation;
use App\Repositories\OrderItem\OrderItemRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\ProductClass\ProductClassRepository;
use App\Repositories\ProductFavorite\ProductFavoriteRepository;
use App\Repositories\Staff\StaffRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class ProductService
{
    private ProductRepository $productRepository;
    private ProductFavoriteRepository $productFavorite;
    private ProductClassRepository $productClass;
    private OrderItemRepository $orderItemRepository;
    private StaffRepository $staffRepository;

    public function __construct(
        ProductRepository $productRepository,
        ProductFavoriteRepository $productFavoriteRepository,
        ProductClassRepository $productClassRepository,
        OrderItemRepository $orderItemRepository,
        StaffRepository $staffRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productFavorite = $productFavoriteRepository;
        $this->productClass = $productClassRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->staffRepository = $staffRepository;
    }

    public function searchProduct($request, $customerId)
    {
        return $this->productRepository->searchProduct($request, $customerId);
    }

    public function getProductBestSaleByCategory($request)
    {
        return $this->productRepository->getProductBestSaleByCategory($request);
    }

    public function getDetailProduct($id, $customerId)
    {
        $product = $this->productRepository->getDetailProduct($id, $customerId);
        if ($product) {
            $product = $product->toArray();
            $configTypes = $product['product_type_config'];
            $productClass = $product['product_classes'];
            $product['product_type_config'] = $this->convertDataProductType($configTypes);
            $product['product_classes'] = $this->convertDataProductClass($productClass);
            return $product;
        }
        return;
    }

    public function getProductReference($id, $customerId)
    {
        return $this->productRepository->getProductReference($id, $customerId);
    }

    public function getProductBestSaleByStore($storeId, $customerId)
    {
        return $this->productRepository->getProductBestSaleByStore(['store_id' => $storeId], $customerId);
    }

    public function likeProduct($customerId, $productId): bool
    {
        return $this->productFavorite->likeProduct($customerId, $productId);
    }

    public function getProductFavorite($sort, $customerId)
    {
        $isFavorite = true;
        return $this->productRepository->searchProduct($sort, $customerId, $isFavorite);
    }

    public function createProduct(array $data)
    {
        // if category_id or brand_id = 0
        if (!$data['category_id']) {
            unset($data['category_id']);
        }
        if (!$data['brand_id']) {
            unset($data['brand_id']);
        }
        return $this->productRepository->create($data);
    }

    public function convertDataProductType($dataProductType): array
    {
        $configName = [];
        $configTypeNew = [];
        foreach ($dataProductType as $configType) {
            $name = $configType['name'];
            if (!in_array($name, $configName)) {
                array_unshift($configName, $name);
                $configTypeNew[$name] = [
                    'name' => $name,
                    'options' => [
                        [
                            'id' => $configType['id'],
                            'type_name' => $configType['type_name']
                        ]
                    ]
                ];
            } else {
                array_unshift($configTypeNew[$name]['options'], [
                    'id' => $configType['id'],
                    'type_name' => $configType['type_name']
                ]);
            }
        }
        return array_values($configTypeNew);
    }

    public function getInfoProductConvert($productId)
    {
        $product = $this->productRepository->getInfoProduct($productId);
        if ($product) {
            $product = $product->toArray();
            $configTypes = $product['product_type_config'];
            $product['product_type_config'] = $this->convertDataProductType($configTypes);
            return $product;
        }
        return;
    }

    public function getAllProductByStore($request, $storeId)
    {
        $products = $this->productRepository->getAllProductByStore($request, $storeId);
        $statusQuantityArr = $this->countProductByStatus($request, $storeId);
        return [
            'products' => $products,
            'status_filter' => $statusQuantityArr
        ];
    }

    public function checkProductHasConfig($productId): bool
    {
        $productClass = $this->productClass->checkProductHasConfig($productId);
        if ($productClass) {
            return true;
        }
        return false;
    }

    public function updateProduct(array $dataProduct, $productId)
    {
        $this->productRepository->update($productId, $dataProduct);
        return $this->productRepository->find($productId);
    }

    public function convertDataProductClass($productClasses)
    {
        foreach ($productClasses as $index => $productClass) {
            $typeConfigId = [];
            foreach ($productClass['product_types'] as $productType) {
                $typeConfigId[] = $productType['product_type_config_id'];
            }
            $productClass['product_types'] = $typeConfigId;
            $productClasses[$index] = $productClass;
        }
        return $productClasses;
    }

    public function checkProductSold($productId): bool
    {
        $item = $this->orderItemRepository->getOrderByProduct($productId);
        return $item ? true : false;
    }

    public function deleteProduct($productId)
    {
        return $this->productRepository->delete($productId);
    }

    public function getListByStoreCMS(array $input)
    {
        $storeId = null;
        $isCMS = true;
        $products = $this->productRepository->getAllProductByStore($input, $storeId, $isCMS);
        $statusQuantityArr = $this->countProductByStatus($input, $storeId);
        return [
            'products' => $products,
            'status_filter' => $statusQuantityArr
        ];
    }

    public function markViolation(int $id, array $condition)
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return responseArrError(JsonResponse::HTTP_NOT_FOUND, [config('errorCodes.product.not_found')]);
        }

        $staff = $this->staffRepository->getOwnerDetail($product->store->id);
        if (!$staff) {
            return responseArrError(JsonResponse::HTTP_NOT_FOUND, [config('errorCodes.staff.not_found')]);
        }

        $condition['status'] = EnumProduct::STATUS_VIOLATION;
        $condition['last_status'] = $product->status;
        $result = $this->productRepository->update($id, $condition);

        // Send mail
        $mailInput = [
            'email' => $staff->email,
            'staff_name' => $staff->name,
            'product_name' => $product->name,
            'product_id' => $product->id,
            'violation_reason' => $condition['violation_reason'] ?? null
        ];
        SendMailMarkViolation::dispatch($mailInput);

        return $result;
    }

    public function unmarkViolation(int $id)
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return false;
        }
        $status = $product->last_status;
        if ($status) {
            return $product->update([
                'status' => $status,
                'last_status' => null
            ]);
        }
        return true;
    }

    // count all product by status
    public function countProductByStatus($request, $storeId)
    {
        if ($request && isset($request['status'])) {
            unset($request['status']);
        }
        $products = $this->productRepository->getAllProductByStore($request, $storeId, false, false);
        $statusQuantityArr = [
            EnumProduct::STATUS_AVAILABLE => [
                'status' => EnumProduct::STATUS_AVAILABLE,
                'quantity' => 0,
            ],
            EnumProduct::STATUS_NO_PUBLIC => [
                'status' => EnumProduct::STATUS_NO_PUBLIC,
                'quantity' => 0,
            ],
            EnumProduct::STATUS_UN_AVAILABLE => [
                'status' => EnumProduct::STATUS_UN_AVAILABLE,
                'quantity' => 0,
            ],
            EnumProduct::STATUS_VIOLATION => [
                'status' => EnumProduct::STATUS_VIOLATION,
                'quantity' => 0,
            ],
        ];
        foreach ($products as $product) {
            switch ($product->status) {
                case EnumProduct::STATUS_PUBLIC:
                    if ($product->stock > 0) {
                        $statusQuantityArr[EnumProduct::STATUS_AVAILABLE]['quantity']++;
                    } else {
                        $statusQuantityArr[EnumProduct::STATUS_UN_AVAILABLE]['quantity']++;
                    }
                    break;
                case EnumProduct::STATUS_NO_PUBLIC:
                    $statusQuantityArr[EnumProduct::STATUS_NO_PUBLIC]['quantity']++;
                    break;
                case EnumProduct::STATUS_VIOLATION:
                    $statusQuantityArr[EnumProduct::STATUS_VIOLATION]['quantity']++;
                    break;
                default:
                    break;
            }
        }
        return array_values($statusQuantityArr);
    }
}
