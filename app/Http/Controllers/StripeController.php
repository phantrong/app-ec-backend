<?php

namespace App\Http\Controllers;

use App\Enums\EnumOrder;
use App\Jobs\JobSendMailPaymentSuccess;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{

    private CartService $cartService;
    private OrderService $orderService;

    public function __construct(
        CartService $cartService,
        OrderService $orderService,
    ) {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }

    public function successCheckoutStripe(Request $request)
    {
        $sessionId = $request->get('session_id');
        $key = $request->get('key');

        if ($sessionId) {
            try {
                $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
                $sessionInfo = $stripe->checkout->sessions->retrieve(
                    $sessionId,
                    []
                );
                $orderId = $sessionInfo->client_reference_id;
                $paid = $sessionInfo->payment_status;
                $paymentIntent = $sessionInfo->payment_intent;

                if ($paid === 'unpaid') {
                    DB::beginTransaction();
                    try {
                        $this->orderService->backQuantityProduct($key, $orderId);
                        $this->orderService->delete($orderId);
                        $this->orderService->deleteSubOrderByOrderIds([$orderId]);
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::channel('payment')->warning('order unpaid: ' . $orderId . ':' . $sessionId);
                        return redirect(getLinkFECart(EnumOrder::PAYMENT_ERROR_UNPAID));
                    }
                }

                $charges = $stripe->charges->all(['payment_intent' => $paymentIntent]);
                $chargesId = $charges->data[0]->id;

                $order = $this->orderService->getOrder($orderId);
                if (!$order) {
                    Log::channel('payment')->error('[ERROR] not find order:' . $orderId . ':' . $sessionId);
                    return redirect(getLinkFECart(EnumOrder::PAYMENT_ERROR));
                };

                $order->stripe_session_id = $sessionId;
                $order->status = EnumOrder::STATUS_PAID;
                $order->save();
                $orderDetail = $this->orderService->getDetailOrder($orderId);
                $customer = $order->customer;
                if (!$customer || ($customer && $customer->send_mail)) {
                    JobSendMailPaymentSuccess::dispatch($orderDetail->toArray());
                }
            } catch (Exception $e) {
                Log::channel('payment')->error('save not save key stripe:' . $e);
                return redirect(getLinkFECart(EnumOrder::PAYMENT_ERROR));
            }

            // transfer
            $connectAccount = $this->orderService->getOrderConnectedAccountPaymentId($orderId);
            foreach ($connectAccount as $item) {
                try {
                    $stripe->transfers->create([
                        'currency' => 'jpy',
                        'destination' => $item['destination'],
                        'amount' => floor($item['amount']  - $item['amount'] * $item['commission']),
                        'source_transaction' => $chargesId,
                        'transfer_group' => 'ORDER_' . $orderId,
                    ]);
                } catch (Exception $e) {
                    Log::channel('payment')->error('[ERROR] transfers destination: '
                        . $item['destination']
                        . ' order: '
                        . $orderId
                        . ' sessionId: '
                        . $sessionId);
                    Log::channel('payment')->error('[ERROR] transfers show: ' . $e);
                }
            }

            DB::beginTransaction();
            try {
                //delete cart item
                $this->cartService->forceDeleteCartItemsByOrderId($key, $orderId);
                $this->orderService->setOrderItemNullCartItem($orderId);
                DB::commit();
                return redirect(getLinkFESuccessPayment());
            } catch (Exception $e) {
                DB::rollBack();
                Log::channel('payment')->warning('not delete cart: ' . $orderId . ':' . $sessionId);
                return redirect(getLinkFECart(EnumOrder::PAYMENT_ERROR));
            }
        }
        return redirect(getLinkFECart(EnumOrder::PAYMENT_ERROR_RESULT));
    }

    public function cancelCheckoutStripe(Request $request)
    {
        $sessionId = $request->get('session_id');
        $key = $request->get('key');

        if ($sessionId) {
            DB::beginTransaction();
            try {
                $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
                $sessionInfo = $stripe->checkout->sessions->retrieve(
                    $sessionId,
                    []
                );
                $stripeId = $sessionInfo->id;
                // $orderId = $sessionInfo->client_reference_id;

                try {
                    $stripe->checkout->sessions->expire($stripeId, []);
                } catch (Exception $e) {
                    Log::channel('payment')->info('======= start cancel expire =======');
                    Log::channel('payment')->error($e);
                    Log::channel('payment')->info('======= end cancel expire =======');
                }

                // run hook expire

                // $order = $this->getOrderByStripe($stripeId, $orderId);
                // if ($order) {
                //     $this->orderService->backQuantityProduct($key, $orderId);
                //     $this->orderService->delete($orderId);
                //     $this->orderService->deleteSubOrderByOrderIds([$orderId]);
                // }

                DB::commit();
                return redirect(getLinkFECart(EnumOrder::PAYMENT_SUCCESS));
            } catch (Exception $e) {
                DB::rollBack();
                Log::channel('payment')->info('======= payment cancel =======');
                Log::channel('payment')->warning($e);
                Log::channel('payment')->info('======= end payment cancel ============');
            }
        }
        return redirect(getLinkFECart(EnumOrder::PAYMENT_ERROR_CANCEL));
    }

    public function getOrderByStripe($stripeId, $orderId)
    {
        return Order::select('id')
            ->where('stripe_session_id', $stripeId)
            ->where('id', $orderId)
            ->where('status', EnumOrder::STATUS_NEW)
            ->first();
    }
}
