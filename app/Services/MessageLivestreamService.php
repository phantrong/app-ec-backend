<?php

namespace App\Services;

use App\Enums\EnumMessageLivestream;
use App\Jobs\AddMessageLivestream;
use App\Models\Customer;
use App\Repositories\LiveStreamMongo\LiveStreamMongoRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MessageLivestreamService
{
    private LiveStreamMongoRepository $livestreamMongoRepository;

    public function __construct(
        LiveStreamMongoRepository $livestreamMongoRepository
    ) {
        $this->livestreamMongoRepository = $livestreamMongoRepository;
    }

    public function getMessageOfChannel($livestreamId, $limit, $end)
    {
        return $this->livestreamMongoRepository->getMessageOfChannel($livestreamId, $limit, $end);
    }

    public function chatMessage($livestreamId, $data)
    {
        $user = Auth::user();
        $data['id'] = Hash::make(rand(0, 10000));
        $data['id'] = str_replace('/', '', $data['id']);
        if ($user instanceof Customer) {
            $data['name'] = $user->name . $user->surname;
            $data['avatar'] = $user->avatar;
            $data['user_id'] = $user->id;
        } else {
            $store = $user->store;
            $data['name'] = $store->name;
            $data['avatar'] = $store->avatar;
            $data['store_id'] = $store->id;
        }
        $data['created_at'] = now()->format('y-m-d H:i:s');
        $data['updated_at'] = now()->format('y-m-d H:i:s');
        AddMessageLivestream::dispatch($livestreamId, $data);
        return $data;
    }

    public function deleteMessage($livestreamId, $messageId)
    {
        return $this->livestreamMongoRepository->deleteMessage($livestreamId, $messageId);
    }

    public function addMessageMongo($livestreamId, $data)
    {
        return $this->livestreamMongoRepository->addMessage($livestreamId, $data);
    }
}
