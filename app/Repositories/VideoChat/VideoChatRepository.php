<?php

namespace App\Repositories\VideoChat;

use App\Enums\EnumVideoChatType;
use App\Models\CalendarStaff;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Store;
use App\Models\VideoChat;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VideoChatRepository extends BaseRepository implements VideoChatRepositoryInterface
{
    public function getModel()
    {
        return VideoChat::class;
    }

    /**
     * Get chat history.
     *
     * @param int $calendarStaffId
     * @return Builder[]|Collection
     */
    public function getChatHistory(int $calendarStaffId)
    {
        $tblVideoChat = $this->model->getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblStaff = Staff::getTableName();
        $tblCustomer = Customer::getTableName();
        $tblStore = Store::getTableName();

        $videoChatStaff = EnumVideoChatType::TYPE_STAFF;
        $videoChatCustomer = EnumVideoChatType::TYPE_CUSTOMER;

        return $this->model->join($tblCalendarStaff, "calendar_staff_id", '=', "$tblCalendarStaff.id")
            ->join($tblCustomer, 'user_id', 'user_id')
            ->join($tblStaff, 'user_id', 'user_id')
            ->join($tblStore, "$tblStaff.store_id", '=', "$tblStore.id")
            ->where('calendar_staff_id', $calendarStaffId)
            ->whereNull("$tblCalendarStaff.deleted_at")
            ->selectRaw("
                $tblVideoChat.id,
                user_id,
                CASE
                    WHEN type = $videoChatStaff THEN $tblStaff.name
                    ELSE $tblCustomer.name
                END AS 'name',
                CASE
                    WHEN type = $videoChatCustomer THEN $tblCustomer.surname
                    ELSE NULL
                END AS 'surname',
                CASE
                    WHEN type = $videoChatStaff THEN $tblStore.avatar
                    ELSE $tblCustomer.avatar
                END AS 'avatar',
                type,
                comment
            ")
            ->groupBy("$tblVideoChat.id")
            ->get();
    }
}
