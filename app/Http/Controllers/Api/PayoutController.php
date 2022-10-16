<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PayoutHistoryRequest;
use App\Services\StripeService;

class PayoutController extends BaseController
{
    private StripeService $stripeService;

    public function __construct(
        StripeService $stripeService
    ) {
        $this->stripeService = $stripeService;
    }

    public function getPayoutHistory(PayoutHistoryRequest $request)
    {
        $stripeId = auth()->user()->store->acc_stripe_id;

        if ($stripeId) {
            $dataPayout = $this->stripeService->getPayoutHistoryStore($stripeId, $request);
            return $this->sendResponse($dataPayout);
        }
        return $this->sendResponse();
    }

    public function getPayoutRetrieve()
    {
        $total = 0;
        $stripeId = auth()->user()->store->acc_stripe_id;

        if ($stripeId) {
            $total = $this->stripeService->getPayoutRetrieveStore($stripeId);
        }
        return $this->sendResponse(['total' => $total]);
    }

    public function getPayoutHistoryCMS(PayoutHistoryRequest $request)
    {
        $dataPayout = $this->stripeService->getPayoutHistoryCMS($request);
        return $this->sendResponse($dataPayout);
    }

    public function countStatusPayout(PayoutHistoryRequest $request)
    {
        try {
            $status = $this->stripeService->countStatusPayout($request);
            return $this->sendResponse($status);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getPayoutHistoryDetailCMS($storeId)
    {
        $dataPayout = $this->stripeService->getPayoutHistoryDetailCMS($storeId);
        return $this->sendResponse($dataPayout);
    }

    public function getPayoutRetrieveCMS($storeId)
    {
        $total = $this->stripeService->getPayoutRetrieveCMS($storeId);
        return $this->sendResponse(['total' => $total]);
    }
}
