<?php

namespace App\Repositories\Messenger;

use App\Repositories\RepositoryInterface;

interface MessengerRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * add message in group mongo
     *
     * @param  array $data
     * @param  string|int $groupId
     * @return mixed
     */
    public function addMessageMongo($groupId, $data);

    /**
     * delete message in group mongo
     *
     * @param  string|int $groupId
     * @param  string|int $messageId
     * @return mixed
     */
    public function deleteMessage($groupId, $messageId);

    /**
     * update message read
     *
     * @param  string|int $groupId
     * @return mixed
     */
    public function updateMessageRead($groupId);

    /**
     * get list group chat of user or store
     *
     * @param  int $customerId
     * @param int $storeId
     * @param int $perPage
     * @return mixed
     */
    public function getListGroupChat($customerId, $storeId, $perPage);

    /**
     * get message of group
     *
     * @param  string $groupId
     * @param int $position
     * @return mixed
     */
    public function getHistoryChat($groupId, $position);

    /**
     * get distinct user in group
     *
     * @param  string $groupId
     * @return mixed
     */
    public function getCustomerInGroup($groupId);

    /**
     * get all message group
     *
     * @param  string $groupId
     * @return object
     */
    public function getMessageGroup($groupId);

    /**
     * update status display of group
     *
     * @param  array $groupId
     * @return object
     */
    public function updateDisplayGroup($groupId);
}
