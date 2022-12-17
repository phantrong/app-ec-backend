<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\EnumSubOrder;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ChangeNoteSubOrderRequest;
use App\Jobs\JobSendMailOrderShipping;
use App\Jobs\JobSendMailReceiveOrder;
use App\Models\Customer;
use App\Services\ExportSubOrderService;
use App\Services\OrderItemService;
use App\Services\OrderService;
use App\Services\SubOrderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class SubOrderController extends BaseController
{
    private SubOrderService $subOrderService;
    private ExportSubOrderService $exportSubOrderService;
    private OrderService $orderService;

    public function __construct(
        SubOrderService $subOrderService,
        OrderItemService $orderItemService,
        ExportSubOrderService $exportSubOrderService,
        OrderService $orderService
    ) {
        $this->subOrderService = $subOrderService;
        $this->orderItemService = $orderItemService;
        $this->exportSubOrderService = $exportSubOrderService;
        $this->orderService = $orderService;
    }

    public function getListSubOrder(Request $request): JsonResponse
    {
        try {
            $fillter = [
                'keyword' => $request->get('keyword'),
                'status' => $request->get('status'),
                'date_start' => $request->get('date_start'),
                'date_end' => $request->get('date_end'),
                'per_page' => $request->get('per_page'),
            ];

            $listOrder = $this->subOrderService->getListSubOrderOfStore($fillter, $request->user()->store_id);

            return $this->sendResponse($listOrder);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getCountSubOrderByStatus(Request $request): JsonResponse
    {
        try {
            $fillter = [
                'keyword' => $request->keyword,
                'arrayStatus' => $request->status,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end
            ];

            $result = $this->subOrderService->countSubOrderByStatusOfStore($fillter, $request->user()->store_id);

            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getDetailSubOrder(Request $request, $id): JsonResponse
    {
        try {
            $subOrder = $this->subOrderService->find($id);
            if (!$subOrder) {
                return $this->sendResponse(null, JsonResponse::HTTP_NOT_FOUND);
            }
            if ($subOrder->store_id != $request->user()->store_id) {
                return $this->sendResponse(null, JsonResponse::HTTP_FORBIDDEN);
            }

            $detailSubOrder = $this->subOrderService->getItemsOfSubOrder($id);

            return $this->sendResponse($detailSubOrder);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function changeStatusSubOrder(Request $request): JsonResponse
    {
        try {
            $subOrder = $this->subOrderService->getDetailOrder($request->sub_order_id);
            if (!$subOrder || !$request->status) {
                return $this->sendResponse(null, JsonResponse::HTTP_NOT_FOUND);
            }
            if ($subOrder->store_id != $request->user()->store_id) {
                return $this->sendResponse(null, JsonResponse::HTTP_FORBIDDEN);
            }
            $attributes = [];
            switch (true) {
                case $request->status == EnumSubOrder::STATUS_SHIPPING
                    && $subOrder->status == EnumSubOrder::STATUS_WAIT_FOR_GOOD:
                    $attributes['status'] = EnumSubOrder::STATUS_SHIPPING;
                    break;
                case $request->status == EnumSubOrder::STATUS_SHIPPED
                    && $subOrder->status == EnumSubOrder::STATUS_SHIPPING:
                    $attributes['status'] = EnumSubOrder::STATUS_SHIPPED;
                    $attributes['completed_at'] = Carbon::now()->toDateTimeString();
                    break;
                default:
                    return $this->sendResponse(null, JsonResponse::HTTP_BAD_REQUEST);
                    break;
            }
            DB::beginTransaction();
            try {
                $this->subOrderService->updateSubOrder($subOrder->id, $attributes);
                $this->orderService->updateSuccessOrder($subOrder->order_id);
                $customer = Customer::find($subOrder->customer_id);
                $emailSendNotify = '';

                if ($customer && $customer->send_mail) {
                    $emailSendNotify = $customer->email;
                }

                if (!$customer) {
                    $shipping = $subOrder->order->shipping;
                    $emailSendNotify = $shipping->email;
                }

                if ($emailSendNotify) {
                    if (isset($attributes['completed_at'])) {
                        JobSendMailReceiveOrder::dispatch($emailSendNotify, $subOrder->toArray());
                    } else {
                        JobSendMailOrderShipping::dispatch($emailSendNotify, $subOrder->toArray());
                    }
                }

                DB::commit();
                return $this->sendResponse(['message' => 'Chuyển trạng thái đơn hàng thành công']);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->sendError($e);
            }
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function exportCsv(Request $request)
    {
        $fillter = [
            'keyword' => $request->keyword,
            'status' => $request->status,
            'date_start' => $request->date_start,
            'date_end' => $request->date_end
        ];

        $storeId = $request->user()->store_id;

        return $this->exportSubOrderService->exportCsv($fillter, $storeId);
    }

    public function changeNoteSubOrder(ChangeNoteSubOrderRequest $request): JsonResponse
    {
        try {
            $subOrder = $this->subOrderService->find($request->sub_order_id);
            if (!$subOrder) {
                return $this->sendResponse(null, JsonResponse::HTTP_NOT_FOUND);
            }
            if ($subOrder->store_id != $request->user()->store_id) {
                return $this->sendResponse(null, JsonResponse::HTTP_FORBIDDEN);
            }
            $attributes = [
                'note' => $request->note
            ];
            $result = $this->subOrderService->updateSubOrder($subOrder->id, $attributes);

            if (!$result) {
                return $this->sendResponse(null, JsonResponse::HTTP_BAD_REQUEST);
            }

            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get order list in CMS.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderListCMS(Request $request)
    {
        try {
            $orders = $this->orderService->getOrderListCMS($request->all());
            return $this->sendResponse($orders);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get order detail in CMS.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getOrderDetailCMS(int $id)
    {
        try {
            $data = $this->orderService->getOrderDetailCMS($id);
            if (isset($data['errorCode'])) {
                return $this->sendResponse($data['errorCode'], $data['status']);
            }
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function exportPdf($subOrderId)
    {
        try {
            $subOrder = $this->subOrderService->getInfoOrderExportPdf($subOrderId);
            $pdf = PDF::loadview('export_pdf.order_detail', compact('subOrder'));
            $fileName = 'order_detail_' . now()->format('Ymd') . '.pdf';
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
