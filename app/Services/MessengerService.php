<?php

namespace App\Services;

use App\Enums\EnumMessenger;
use App\Enums\EnumVideoCallType;
use App\Models\Customer;
use App\Repositories\Customer\CustomerRepository;
use App\Repositories\Messenger\MessengerRepository;
use App\Repositories\Staff\StaffRepository;
use App\Repositories\Store\StoreRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MessengerService
{
    private MessengerRepository $messengerRepository;
    private StaffRepository $staffRepository;
    private CustomerRepository $customerRepository;
    private StoreRepository $storeRepository;

    public function __construct(
        MessengerRepository $messengerRepository,
        StaffRepository $staffRepository,
        CustomerRepository $customerRepository,
        StoreRepository $storeRepository
    ) {
        $this->messengerRepository = $messengerRepository;
        $this->staffRepository = $staffRepository;
        $this->customerRepository = $customerRepository;
        $this->storeRepository = $storeRepository;
    }

    public function createGroupChat($userId, $storeId, $type, $roomId = null)
    {
        $customer = $this->customerRepository->find($userId);
        $store = $this->storeRepository->find($storeId);
        if ($customer && $store) {
            $dataCreate = [
                'type' => (int) $type,
                'avatar' => [
                    [
                        'image' => $store->avatar,
                        'name' => $store->name,
                        'store_id' => (int) $store->id
                    ],
                    [
                        'image' => $customer->avatar,
                        'name' => $customer->name,
                        'user_id' => (int) $userId
                    ]
                ]
            ];

            //if chat video call
            if ($roomId) {
                $dataCreate['group_id'] = $roomId;
                $dataCreate['hidden'] = [EnumMessenger::HIDDEN];
            }
            return $this->messengerRepository->create($dataCreate);
        }
        return;
    }

    public function getDataMessage($data)
    {
        $user = Auth::user();
        $idMessage = str_replace('/', '_', Hash::make(rand(0, 10000)));
        $data['id'] = $idMessage;
        $now = now()->format('Y-m-d H:i:s');
        $dataAddMessage = [
            'id' => $idMessage,
            'content' => $data['content'],
            'created_at' => $now,
            'updated_at' => $now
        ];
        $data['created_at'] = $now;
        if ($user instanceof Customer) {
            $data['user_id'] = $user->id;
            $data['name'] = $user->name;
            $data['avatar'] = $user->avatar;
            $dataAddMessage['user_id'] = $user->id;
        } else {
            $store = $user->store;
            $data['store_id'] = $store->id;
            $data['name'] = $store->name;
            $data['avatar'] = $store->avatar;
            $dataAddMessage['store_id'] = $store->id;
        }
        return [
            'data' => $data,
            'data_add_message' => $dataAddMessage
        ];
    }

    public function addMessage($groupId, $data)
    {
        $data = $this->getDataMessage($data);
        $dataAddMessage = $data['data_add_message'];
        $this->messengerRepository->addMessageMongo($groupId, $dataAddMessage);
        return $data['data'];
    }

    public function deleteMessage($groupId, $messageId)
    {
        return $this->messengerRepository->deleteMessage($groupId, $messageId);
    }

    public function updateMessageRead($groupId)
    {
        return $this->messengerRepository->updateMessageRead($groupId);
    }

    public function updateMessageUnRead($groupId)
    {
        return $this->messengerRepository->updateMessageUnRead($groupId);
    }

    public function getListGroupChat($perPage)
    {
        $user = Auth::user();
        $customerId = null;
        $storeId = null;
        if ($user instanceof Customer) {
            $customerId = $user->id;
        } else {
            $storeId = $user->store->id;
        }
        return $this->messengerRepository->getListGroupChat($customerId, $storeId, $perPage);
    }

    public function getHistoryChat($groupId, $position)
    {
        return $this->messengerRepository->getHistoryChat($groupId, $position);
    }

    public function getGroupChat($storeId, $userId)
    {
        return $this->messengerRepository->getGroupChat($storeId, $userId);
    }

    public function countMessageGroup($groupId)
    {
        $group = $this->messengerRepository->getMessageGroup($groupId);
        return isset($group->messages) ? count($group->messages) : 0;
    }

    public function updateInfoUser($userId, $data)
    {
        return $this->messengerRepository->updateInfoUser($userId, $data);
    }

    public function updateInfoShop($storeId, $data)
    {
        return $this->messengerRepository->updateInfoShop($storeId, $data);
    }
}
