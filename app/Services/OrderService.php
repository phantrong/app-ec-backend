<?php

namespace App\Services;

use App\Enums\EnumOrder;
use App\Enums\EnumSubOrder;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Customer;
use App\Models\Shipping;
use App\Models\Store;
use App\Models\SubOrder;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\OrderItem\OrderItemRepository;
use App\Repositories\ProductClass\ProductClassRepository;
use App\Repositories\Shipping\ShippingRepository;
use App\Repositories\SubOrder\SubOrderRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    private CartRepository $cartRepository;
    private OrderRepository $orderRepository;
    private SubOrderRepository $subOrderRepository;
    private OrderItemRepository $orderItemRepository;
    private ShippingRepository $shippingRepository;
    private ProductClassRepository $productClassRepository;

    public function __construct(
        CartRepository $cartRepository,
        OrderRepository $orderRepository,
        SubOrderRepository $subOrderRepository,
        OrderItemRepository $orderItemRepository,
        ShippingRepository $shippingRepository,
        productClassRepository $productClassRepository,
    ) {
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->subOrderRepository = $subOrderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->shippingRepository = $shippingRepository;
        $this->productClassRepository = $productClassRepository;
    }

    public function getOrder($orderId)
    {
        return $this->orderRepository->find($orderId);
    }

    public function delete($orderId)
    {
        return $this->orderRepository->delete($orderId);
    }

    public function createOrder($key, $cartItemIds)
    {
        $now = Carbon::now();
        $cart = $this->cartRepository->getProductUseCreateOrder($key, $cartItemIds);
        $customerId = auth('sanctum')->check() ? auth('sanctum')->user()->id : null;

        $dataOrder = [];
        $dataStores = [];
        $dataProductItem = [];
        $countProductCanPay = 0;

        if ($cart) {
            foreach ($cart->cartItem as $item) {
                if (!$item->products) {
                    continue;
                }

                $product = $item->products;
                $priceProduct = $product->discount;
                $priceProductOrder = (float)$priceProduct * $item->quantity;

                if ($item->products && $product->store) {
                    if (isset($dataStores[$product->store_id])) {
                        $dataStores[$product->store_id]['total'] += $priceProductOrder;
                        $dataStores[$product->store_id]['total_payment'] += $priceProductOrder - 0; //discount
                    } else {
                        $dataStores[$product->store_id] = [
                            'store_id' => $product->store->id,
                            'commission' => @$product->store->commission ?? 0,
                            'total' => $priceProductOrder,
                            'total_payment' => $priceProductOrder,
                            'verified_at' => $now,
                            'code' => $product->store->code,
                            'status' => EnumOrder::STATUS_NEW,
                        ];
                    }
                }

                if ($dataOrder) {
                    $dataOrder['total'] += $priceProductOrder;
                    $dataOrder['total_payment'] += $priceProductOrder - 0; //discount
                } else {
                    $dataOrder = [
                        'total' => $priceProductOrder,
                        'total_payment' => $priceProductOrder,
                        'status' => EnumOrder::STATUS_PAID,
                    ];
                }

                $dataItem = [
                    'sub_order_id' => null,
                    'cart_item_id' => $item->id,
                    'product_id' => $product->id,
                    'price' => (float)$priceProduct,
                    'quantity' => $item->quantity,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $dataProductItem[$product->store_id][] = $dataItem;
                $countProductCanPay++;
            }
        }

        DB::beginTransaction();
        try {
            $dataOrder['customer_id'] = $customerId;
            if ($countProductCanPay !== count($cartItemIds)) {
                DB::rollBack();
                return responseArrError(
                    JsonResponse::HTTP_BAD_REQUEST,
                    [config('errorCodes.cart.not_add_order_error')]
                );
            }

            $order = $this->addOrder($dataOrder);
            if ($order) {
                foreach ($dataStores as $store) {
                    $store['sub_order_code'] = $order->order_code;
                    $store['order_id'] = $order->id;
                    $store['status'] = EnumSubOrder::STATUS_WAIT_FOR_GOOD;
                    $store['code'] = $order->order_code . '_' . $store['code'];
                    $storeCollection = $this->addSubOrder($store);
                    if ($storeCollection && isset($dataProductItem[$store['store_id']])) {
                        $dataOrderItem = [];
                        foreach ($dataProductItem[$store['store_id']] as $product) {
                            $product['sub_order_id'] = $storeCollection->id;
                            $dataOrderItem[] = $product;
                        }
                        $this->addOderItems($dataOrderItem);
                    }
                }
            } else {
                DB::rollBack();
                return responseArrError(
                    JsonResponse::HTTP_BAD_REQUEST,
                    [config('errorCodes.cart.not_add_order_error')]
                );
            }
            DB::commit();
            return $order;
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e);
            return responseArrError(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function addOrder($dataOrder)
    {
        return $this->orderRepository->createOrder($dataOrder);
    }

    private function addSubOrder($dataOrder)
    {
        return $this->subOrderRepository->create($dataOrder);
    }

    private function addOderItems($dataOrderItem)
    {
        return $this->orderItemRepository->insert($dataOrderItem);
    }

    public function createAddressShip($orderId, CreateOrderRequest $request)
    {
        $dataShip = $request->only([
            'address',
        ]);
        $customer = auth('sanctum')->user() ?? null;
        if ($customer) {
            $dataShip['email'] = $customer->email;
            $dataShip['receiver_name'] = $customer->name;
            $dataShip['phone_number'] = $customer->phone;
        }
        $dataShip['order_id'] = $orderId;

        return $this->shippingRepository->create($dataShip);
    }

    public function getOrderFlatFormPaymentId($orderId)
    {
        $order = $this->subOrderRepository->getOrderPaymentById($orderId);
        $dataResultProduct = [];

        if ($order) {
            foreach ($order as $store) {
                if ($store->store->acc_stripe_id) {
                    foreach ($store->orderItems as $orderItem) {
                        $data['price_data'] = [
                            'currency' => 'jpy',
                            'product_data' => [
                                'name' => $orderItem->product->name,
                                'images' => [$orderItem->product->productMediasImage->media_path]
                            ],
                            'unit_amount' => (int) $orderItem->price,
                        ];
                        if ($orderItem->quantity) {
                            $data['quantity'] = $orderItem->quantity;
                            $dataResultProduct[] = $data;
                        }
                    }
                }
            }
        }
        return $dataResultProduct;
    }

    public function getOrderConnectedAccountPaymentId($orderId)
    {
        $dataResultStore = [];
        $orders = $this->subOrderRepository->getOrderPaymentById($orderId);

        if ($orders) {
            foreach ($orders as $order) {
                $dataStore = [];
                $dataStore['destination'] = $order->store->acc_stripe_id;
                $dataStore['commission'] = @$order->commission ?? 0;
                $dataStore['amount'] = 0;

                if ($dataStore['destination']) {
                    foreach ($order->orderItems as $orderItem) {
                        $dataStore['amount'] += (int) $orderItem->price * $orderItem->quantity;
                    }
                    if ($dataStore['amount']) {
                        $dataResultStore[] = $dataStore;
                    }
                }
            }
        }

        return $dataResultStore;
    }

    public function backQuantityProduct($key, $orderId)
    {
        $cartItemIds = $this->orderItemRepository->getOrderItemByOrderNew($orderId)->pluck('cart_item_id')->toArray();
        if ($cartItemIds) {
            return $this->productClassRepository->updateQuantityProductWithCart($key, $cartItemIds, false);
        }
        return;
    }

    public function setOrderItemNullCartItem($orderId)
    {
        return $this->orderItemRepository->setOrderItemNullCartItem($orderId);
    }

    /**
     * Get order list in CMS.
     *
     * @param array $condition
     * @return array
     */
    public function getOrderListCMS(array $condition)
    {
        $tblSubOrder = SubOrder::getTableName();
        $tblStore = Store::getTableName();
        $tblShipping = Shipping::getTableName();
        $columns = [
            "$tblSubOrder.id",
            "$tblSubOrder.code",
            "$tblStore.name AS store_name",
            "$tblShipping.receiver_name AS customer_name",
            "$tblSubOrder.created_at",
            "$tblSubOrder.total_payment",
            "$tblSubOrder.status",
        ];
        $subOrderList = $this->subOrderRepository->getOrderList($condition, $columns);

        $totalPayment = 0;
        $revenueAdmin = 0;
        $subOrderStatusListArr = [
            EnumSubOrder::STATUS_WAIT_CONFIRM,
            EnumSubOrder::STATUS_WAIT_FOR_GOOD,
            EnumSubOrder::STATUS_SHIPPING,
            EnumSubOrder::STATUS_SHIPPED,
        ];
        $subOrderStatusArr = [];
        foreach ($subOrderStatusListArr as $subOrderStatus) {
            $subOrderStatusArr[$subOrderStatus] = [
                'status' => $subOrderStatus,
                'quantity' => 0,
            ];
        }
        $subOrderAll = $this->subOrderRepository->getOrderList($condition, $columns, false);
        if ($subOrderAll) {
            $totalPaymentSubOrdersArr = array_column(collect($subOrderAll)->toArray(), 'total_payment');
            $totalPayment = array_sum($totalPaymentSubOrdersArr);

            foreach ($subOrderAll as $subOrder) {
                $revenueAdmin += (float) $subOrder->revenue_admin;
            }

            $subOrderStatusList = $this->subOrderRepository->getQuantityEachStatus($condition);
            foreach ($subOrderStatusList as $subOrder) {
                if (isset($subOrderStatusArr[$subOrder->status])) {
                    $subOrderStatusArr[$subOrder->status]['quantity'] = $subOrder->quantity;
                }
            }
        }

        return [
            'total_payment' => $totalPayment,
            'revenue_admin' => $revenueAdmin,
            'orders' => $subOrderList,
            'status_filter' => array_values($subOrderStatusArr),
        ];
    }

    /**
     * Get order detail in CMS.
     *
     * @param int $id
     * @return mixed
     */
    public function getOrderDetailCMS(int $id)
    {
        $subOrderStatusArr = [
            EnumSubOrder::STATUS_WAIT_FOR_GOOD,
            EnumSubOrder::STATUS_SHIPPING,
            EnumSubOrder::STATUS_SHIPPED,
        ];
        $order = $this->subOrderRepository->getDetailSubOrder($id, $subOrderStatusArr);
        if (!$order) {
            return responseArrError(JsonResponse::HTTP_NOT_FOUND, [config('errorCodes.order.not_found')]);
        }
        return $order;
    }

    public function updateSuccessOrder($orderId)
    {
        return $this->orderRepository->updateSuccessOrder($orderId);
    }

    public function getDetailOrder($orderId)
    {
        return $this->orderRepository->getDetailOrder($orderId);
    }

    public function deleteSubOrderByOrderIds($orderIds)
    {
        return $this->subOrderRepository->deleteSubOrderByOrderIds($orderIds);
    }
}
