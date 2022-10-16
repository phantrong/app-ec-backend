<?php

namespace App\Repositories\LiveStreamMongo;

use App\Repositories\RepositoryInterface;

interface LivestreamMongoRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * get list messages live stream
     *
     * @param  integer $livestreamId
     * @param  integer $start
     * @param  integer $end
     * @return object
     */
    public function getMessageOfChannel($livestreamId, $start, $end);

    /**
     * add message in channel when have comment
     *
     * @param  integer $livestreamId
     * @param  array $data
     * @return object
     */
    public function addMessage($livestreamId, $data);

    /**
     * delete message in channel
     *
     * @param  integer $livestreamId
     * @param  integer $messageId
     * @return object
     */
    public function deleteMessage($livestreamId, $messageId);
}
