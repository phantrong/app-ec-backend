<?php

namespace App\Services;

use App\Repositories\VideoChat\VideoChatRepository;

class VideoChatService
{
    private VideoChatRepository $videoChatRepository;

    public function __construct(VideoChatRepository $videoChatRepository)
    {
        $this->videoChatRepository = $videoChatRepository;
    }

    public function updateVideoChat($videoId, $data)
    {
        return $this->videoChatRepository->update($videoId, $data);
    }
}
