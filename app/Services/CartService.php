<?php

namespace App\Services;

use App\Enums\EnumProduct;
use App\Repositories\CartItem\CartItemRepository;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\OrderItem\OrderItemRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\ProductClass\ProductClassRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartService
{
    private CartItemRepository $cartItemRepository;
    private CartRepository $cartRepository;
    private ProductRepository $productRepository;
    private OrderItemRepository $orderItemRepository;

    public function __construct(
        CartItemRepository $cartItemRepository,
        CartRepository $cartRepository,
        ProductRepository $productRepository,
        OrderItemRepository $orderItemRepository
    ) {
        $this->cartItemRepository = $cartItemRepository;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->orderItemRepository = $orderItemRepository;
    }

    public function addCart($productId, $quantity, $key)
    {
        $product = $this->productRepository->find($productId);
        if (!$product) {
            return responseArrError(JsonResponse::HTTP_NOT_FOUND, [config('errorCodes.cart.product_class_not_found')]);
        }
        $cart = $this->cartRepository->getCartByKey($key);

        DB::beginTransaction();
        try {
            if ($cart) {
                $result = $this->updateInsertCartItem($cart, $product, $quantity);
                if (is_array($result)) {
                    return $result;
                }
            } else {
                if ((int)$quantity === 0) {
                    return responseArrError(
                        JsonResponse::HTTP_NOT_ACCEPTABLE,
                        [config('errorCodes.cart.product_less_than_0')]
                    );
                }
                if ($product->stock < $quantity) {
                    return responseArrError(
                        JsonResponse::HTTP_NOT_ACCEPTABLE,
                        [config('errorCodes.cart.not_enough_products_quantity')],
                        $product->only(['id', 'stock'])
                    );
                }

                $cartNew = $this->cartRepository->createCart($key);
                $this->cartItemRepository->createCarItem(
                    $cartNew->id,
                    $product->id,
                    $quantity,
                );
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e);
            return responseArrError(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * update or install
     *
     * @param collection $cart
     * @param collection $productClassIdAdd
     * @param int $quantity
     * @return array|void
     */
    private function updateInsertCartItem($cart, $productAdd, $quantity)
    {
        $productUpdate = $cart->cartItem->where('product_id', $productAdd->id)->first();

        if ($productUpdate) {
            $productUpdate->quantity += $quantity;

            if ($productUpdate->quantity <= 0) {
                return responseArrError(
                    JsonResponse::HTTP_NOT_ACCEPTABLE,
                    [config('errorCodes.cart.product_less_than_0')]
                );
            }
            if ($productAdd->stock == 0) {
                return responseArrError(
                    JsonResponse::HTTP_NOT_ACCEPTABLE,
                    [config('errorCodes.cart.product_out_of_stock')]
                );
            }
            if ($productAdd->stock < $productUpdate->quantity) {
                return responseArrError(
                    JsonResponse::HTTP_NOT_ACCEPTABLE,
                    [config('errorCodes.cart.not_enough_products_quantity')],
                    $productAdd->only(['id', 'stock'])
                );
            }

            $productUpdate->save();
        } else {
            if ($productAdd->stock < $quantity) {
                return responseArrError(
                    JsonResponse::HTTP_NOT_ACCEPTABLE,
                    [config('errorCodes.cart.not_enough_products_quantity')],
                    $productAdd->only(['id', 'stock'])
                );
            }

            $this->cartItemRepository->createCarItem(
                $cart->id,
                $productAdd->id,
                $quantity,
            );
        }
        return;
    }

    /**
     * list product cart
     *
     * @param string|int $key (cart key and customerId)
     * @param int|null $customerId
     * @return collection
     */
    public function listProductCart($key)
    {
        $cart = $this->cartRepository->getProductByKey($key);
        // return $cart;
        $dataProduct = [];
        $dataStore = [];
        if ($cart) {
            foreach ($cart->cartItem as $item) {
                if (!$item->products) {
                    continue;
                }
                $product = $item->products;

                if ($item->products &&
                    $product->store &&
                    !isset($dataStore[$product->store_id])
                ) {
                    $dataStore[$product->store_id] = [
                        'store_id' => $product->store->id,
                        'store_name' => $product->store->name,
                        'store_image' => $product->store->avatar,
                    ];
                }

                $arrProduct = [
                    'cart_item_id' => $item->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->productMediasImage ? $product->productMediasImage->media_path : '',
                    'price' => (float)$item->products->price,
                    'price_discount' => (float)$item->products->discount,
                    'stock' => $item->products->stock,
                    'quantity' => $item->quantity,
                ];
                $dataProduct[$product->store_id][] = $arrProduct;
            }
        }

        $dataResult = [];
        foreach ($dataStore as $idStore => $store) {
            $data = $store;
            $data['products'] = $dataProduct[$idStore];
            $dataResult[] = $data;
        }

        return collect($dataResult);
    }

    public function deleteProductCart($productClassId, $cartDelete)
    {
        return $this->cartItemRepository->forceDeletedCartItemByProduct($productClassId, $cartDelete);
    }

    /**
     * minus product quantity
     *
     * @param  string $key (cart key or customer id)
     * @param  array $cartItemIds
     * @return void
     */
    public function updateQuantityProductWithCart($key, $cartItemIds)
    {
        DB::beginTransaction();
        try {
            $productsOutOfStock = $this->productClassRepository->getNegativeProduct($key, $cartItemIds, '=');
            if (count($productsOutOfStock)) {
                DB::rollBack();
                return responseArrError(
                    JsonResponse::HTTP_NOT_ACCEPTABLE,
                    [config('errorCodes.cart.product_out_of_stock')],
                    $productsOutOfStock->only(['id', 'stock'])
                );
            }

            $this->productClassRepository->updateQuantityProductWithCart($key, $cartItemIds);
            $productNegative = $this->productClassRepository->getNegativeProduct($key, $cartItemIds);
            if (count($productNegative)) {
                DB::rollBack();
                return responseArrError(
                    JsonResponse::HTTP_NOT_ACCEPTABLE,
                    [config('errorCodes.cart.not_enough_products_quantity')],
                    $productNegative->only(['id', 'stock'])
                );
            }
            DB::commit();
            return;
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e);
            return responseArrError(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function forceDeleteCartItems($key, $cartItemIds)
    {
        return $this->cartItemRepository->forceDeleteCartItemIds($key, $cartItemIds);
    }

    public function forceDeleteCartItemsByOrderId($key, $orderId)
    {
        $cartItemIds = $this->orderItemRepository->getOrderItemByOrderNew($orderId)->pluck('cart_item_id')->toArray();
        if ($cartItemIds) {
            return $this->cartItemRepository->forceDeleteCartItemIds($key, $cartItemIds);
        }
        return;
    }

    public function viewSortCart($key)
    {
        return $this->cartRepository->getCartOverView($key);
    }
}
