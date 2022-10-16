<?php

namespace App\Repositories\Messenger;

use App\Models\ChatMessageMongo;
use App\Enums\EnumMessenger;
use App\Models\Customer;
use App\Models\Messenger;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Auth;

class MessengerRepository extends BaseRepository implements MessengerRepositoryInterface
{
    const PER_PAGE = 10;

    public function getModel()
    {
        return ChatMessageMongo::class;
    }

    public function addMessageMongo($groupId, $data)
    {
        $group = $this->model
            ->where('_id', $groupId)
            ->first();
        $group->update(['updated_at' => now()->format('Y-m-d H:i:s')]);
        return $group->push('messages', $data);
    }

    public function deleteMessage($groupId, $messageId)
    {
        return $this->model
            ->where('_id', $groupId)
            ->where('messages.id', $messageId)
            ->push(
                'messages.$.deleted_at',
                now()->format('Y-m-d H:i:s')
            );
    }

    public function updateMessageRead($groupId)
    {
        $index = EnumMessenger::INDEX_STORE;
        $user = Auth::user();
        if ($user instanceof Customer) {
            $index = EnumMessenger::INDEX_USER;
        }
        return $this->model
            ->where('_id', $groupId)
            ->pull('avatar.' . $index . '.read', [EnumMessenger::UN_READ]);
    }

    public function updateMessageUnRead($groupId)
    {
        $index = EnumMessenger::INDEX_USER;
        $user = Auth::user();
        if ($user instanceof Customer) {
            $index = EnumMessenger::INDEX_STORE;
        }
         return $this->model
            ->where('_id', $groupId)
             ->push('avatar.' . $index . '.read', EnumMessenger::UN_READ, true);
    }

    public function getListGroupChat($customerId, $storeId, $perPage)
    {
        return $this->model
            ->select('_id', 'type', 'messages', 'avatar')
            ->where('hidden', 'exists', false)
            ->project(['messages' => ['$slice' => -1]])
            ->when($customerId, function ($query) use ($customerId) {
                return $query->where('messages.user_id', $customerId)
                    ->orWhere('avatar.1.user_id', $customerId);
            })
            ->when($storeId, function ($query) use ($storeId) {
                return $query->where('avatar.0.store_id', $storeId);
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function getHistoryChat($groupId, $position)
    {
        return $this->model
            ->select('_id', 'messages')
            ->where('_id', $groupId)
            ->project(['messages' => ['$slice' => [- (int) $position, self::PER_PAGE]]])
            ->first();
    }

    public function getCustomerInGroup($groupId)
    {
        return $this->model->select('messages.user_id')
            ->where('_id', $groupId)
            ->distinct('messages.user_id')
            ->get();
    }

    public function getGroupChat($storeId, $userId)
    {
        return $this->model
            ->select('_id', 'avatar', 'type')
            ->where('avatar.user_id', (int) $userId)
            ->where('avatar.store_id', (int) $storeId)
            ->where('type', EnumMessenger::CHAT_MESSENGER)
            ->first();
    }

    public function getMessageGroup($groupId)
    {
        return $this->model->select('messages')
            ->where('_id', $groupId)
            ->first();
    }

    public function updateDisplayGroup($groupId)
    {
        return $this->model
            ->whereIn('groupId', $groupId)
            ->update(['isHidden' => false]);
    }

    public function updateInfoUser($userId, $data)
    {
        $index = EnumMessenger::INDEX_USER;
        if ($this->model->count()) {
            return $this->model
                ->where("people.$index.userId", $userId)
                ->update(["people.$index" => $data]);
        }
        return null;
    }

    public function updateInfoShop($storeId, $data)
    {
        $index = EnumMessenger::INDEX_STORE;
        if ($this->model->count()) {
            return $this->model
                ->where("people.$index.storeId", $storeId)
                ->update(["people.$index" => $data]);
        }
        return null;
    }
}
