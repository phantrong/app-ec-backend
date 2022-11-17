<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnumStripe;
use App\Http\Requests\AddCartRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\DeletedCartRequest;
use App\Http\Requests\ListCartRequest;
use App\Services\CartService;
use App\Services\OrderService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends BaseController
{
    private CartService $cartService;
    private OrderService $orderService;

    public function __construct(CartService $cartService, OrderService $orderService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }

    /**
     * addCart
     *
     * @param  mixed $request
     * @return JsonResponse
     */
    public function addCart(AddCartRequest $request): JsonResponse
    {
        try {
            $key = auth('sanctum')->check() ? auth('sanctum')->user()->id : $request->cart_key;
            $productId = (int) $request->product_id;
            $quantity = (int) $request->quantity;
            $result =  $this->cartService->addCart($productId, $quantity, $key);
            if (is_array($result)) {
                return $this->sendResponse($result['errorCode'], $result['status'], $result['data']);
            }
            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function listProduct(ListCartRequest $request): JsonResponse
    {
        try {
            $key = auth('sanctum')->check() ? auth('sanctum')->user()->id : $request->cart_key;
            return $this->sendResponse($this->cartService->listProductCart($key));
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function deleteProduct(DeletedCartRequest $request): JsonResponse
    {
        try {
            $cartKey = auth('sanctum')->check() ? auth('sanctum')->user()->id : $request->cart_key;
            $productClassId = (int) $request->product_class_id;

            return $this->sendResponse($this->cartService->deleteProductCart($productClassId, $cartKey));
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function viewSortCart(Request $request)
    {
        try {
            $key = auth('sanctum')->user()->id ?? $request->cart_key;
            $cart = $this->cartService->viewSortCart($key);
            return $this->sendResponse($cart);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function createOrder(CreateOrderRequest $request)
    {
        $key = auth('sanctum')->check() ? auth('sanctum')->user()->id : $request->cart_key;
        $cartItemIds = $request->cart_item_ids;

        DB::beginTransaction();
        try {
            $result = $this->cartService->updateQuantityProductWithCart($key, $cartItemIds);
            if (is_array($result)) {
                return $this->sendResponse($result['errorCode'], $result['status'], $result['data']);
            }
            $order = $this->orderService->createOrder($key, $cartItemIds);
            if (is_array($order)) {
                return $this->sendResponse($order['errorCode'], $order['status']);
            }
            $this->orderService->createAddressShip($order->id, $request);

            $paymentOrder = $this->getOrderPaymentById($order->id, $key);
            if (!$paymentOrder) {
                DB::rollBack();
                return $this->sendResponse(
                    [config('errorCodes.cart.order_not_item')],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            if (isRunPaymentStripe()) {
                $session = $this->sendResponse($this->postCheckoutStripe($paymentOrder, $order));
                DB::commit();
                return $session;
            }

            // run fake when not payment stripe
            $order->stripe_session_id = 'payment fake run test';
            $order->save();
            $this->cartService->forceDeleteCartItemsByOrderId($key, $order->id);
            $this->orderService->setOrderItemNullCartItem($order->id);
            DB::commit();
            return $this->sendResponse([
                'url' => getLinkFESuccessPayment()
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e);
            return $this->sendError($e);
        }
    }

    private function getOrderPaymentById($orderId, $key)
    {
        $dataResult = [];
        $dataProduct = $this->orderService->getOrderFlatFormPaymentId($orderId);

        if ($dataProduct) {
            $dataResult['line_items'] = $dataProduct;
            $dataResult['mode'] = 'payment';
            $dataResult['success_url'] = config('app.url')
                . "/checkout-session/success?session_id={CHECKOUT_SESSION_ID}&key=$key";
            $dataResult['cancel_url'] = config('app.url')
                . "/checkout-session/cancel?session_id={CHECKOUT_SESSION_ID}&key=$key";
            $dataResult['client_reference_id'] = $orderId;
            return $dataResult;
        }
        return [];
    }

    private function postCheckoutStripe($data, $order)
    {
        $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
        $data['expires_at'] = Carbon::now('UTC')
            ->addHour(EnumStripe::TIME_OUT_CHECKOUT)->timestamp;
        $session = $stripe->checkout->sessions->create($data);

        $order->stripe_session_id = $session->id;
        $order->save();

        return [
            'url' => $session->url
        ];
    }
}
