<?php

namespace App\Repositories\LiveStreamMongo;

use App\Models\LivestreamMongo;
use App\Repositories\BaseRepository;
use function now;

class LiveStreamMongoRepository extends BaseRepository implements LivestreamMongoRepositoryInterface
{
    public function getModel(): string
    {
        return LivestreamMongo::class;
    }

    public function createLivestream($livestreamId)
    {
        return $this->model->create([
            'livestream_id' => $livestreamId
        ]);
    }

    public function getMessageOfChannel($livestreamId, $start, $end)
    {
        return $this->model
            ->select('id', 'messages')
            ->where('livestream_id', (int) $livestreamId)
            ->project(['messages' => ['$slice' => [(int) $start, (int) $end]]])
            ->first();
    }

    public function addMessage($livestreamId, $data)
    {
        return $this->model
            ->where('livestream_id', (int) $livestreamId)
            ->push('messages', $data);
    }

    public function deleteMessage($livestreamId, $messageId)
    {
        return $this->model
            ->where('livestream_id', (int) $livestreamId)
            ->where('messages.id', $messageId)
            ->update([
                'messages.$.deleted_at' => now()->format('Y-m-d H:i:s')
            ]);
    }
}
