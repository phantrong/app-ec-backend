<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnumMessenger;
use App\Events\ChatMessengerEvent;
use App\Http\Requests\AddMessageRequest;
use App\Http\Requests\AddMessageShopRequest;
use App\Services\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessengerController extends BaseController
{
    const PERPAGE = 10;

    private MessengerService $messengerService;

    public function __construct(MessengerService $messengerService)
    {
        $this->messengerService = $messengerService;
    }

    public function getHistoryChat(Request $request, $groupId)
    {
        try {
            $numberMessage = $this->messengerService->countMessageGroup($groupId);
            $listMessage = $this->messengerService->getHistoryChat($groupId, $request->position);
            if ($listMessage) {
                $listMessage->total_messsage = $numberMessage;
                return $this->sendResponse($listMessage);
            }
            return $this->sendResponse(null, JsonResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getListGroupChat(Request $request)
    {
        try {
            $perPage = $request->per_page ?? self::PERPAGE;
            $listGroups = $this->messengerService->getListGroupChat($perPage);
            return $this->sendResponse($listGroups);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getGroupChatUser(Request $request)
    {
        try {
            $userId = Auth::id();
            $storeId = (int) $request->store_id;
            $group = $this->messengerService->getGroupChat($storeId, $userId);
            $group = $group ?: $this->messengerService->createGroupChat(
                $userId,
                $storeId,
                EnumMessenger::CHAT_MESSENGER,
                $request->room_id
            );
            return $this->sendResponse($group);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getGroupChatShop(Request $request)
    {
        try {
            $storeId = Auth::user()->store_id;
            $userId = (int) $request->user_id;
            $group = $this->messengerService->getGroupChat($storeId, $userId);
            $group = $group ?: $this->messengerService->createGroupChat(
                $userId,
                $storeId,
                EnumMessenger::CHAT_MESSENGER,
                $request->room_id
            );
            return $this->sendResponse($group);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function createGroupChatUser(Request $request)
    {
        try {
            return $this->messengerService->createGroupChat(
                Auth::id(),
                $request->store_id,
                $request->type,
                $request->room_id
            );
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function createGroupChatShop(Request $request)
    {
        try {
            return $this->messengerService->createGroupChat(
                $request->customer_id,
                Auth::user()->store_id,
                $request->type,
                $request->room_id
            );
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function addMessage(AddMessageRequest $request, $groupId)
    {
        DB::beginTransaction();
        try {
            $messageNew = $this->messengerService->addMessage($groupId, $request->only('content'));
            $this->messengerService->updateMessageUnRead($groupId);
            DB::commit();
            event(new ChatMessengerEvent($messageNew));
            return $this->sendResponse($messageNew);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function deleteMessage($groupId, $messageId)
    {
        try {
            $this->messengerService->deleteMessage($groupId, $messageId);
            return $this->sendResponse();
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateMessageRead($groupId)
    {
        try {
            return $this->messengerService->updateMessageRead($groupId);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }
}
