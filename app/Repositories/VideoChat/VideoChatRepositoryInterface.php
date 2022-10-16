<?php

namespace App\Repositories\VideoChat;

use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface VideoChatRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * Get chat history.
     *
     * @param int $calendarStaffId
     * @return Builder[]|Collection
     */
    public function getChatHistory(int $calendarStaffId);
}
