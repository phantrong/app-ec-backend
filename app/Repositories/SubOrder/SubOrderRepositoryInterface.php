<?php

namespace App\Repositories\SubOrder;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface SubOrderRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * getListSubOrderOfStore
     *
     * @param  array  $fillter
     * @param  int  $storeId
     * @return object
     */
    public function getListSubOrderOfStore($fillter, $storeId);

    /**
     * countSubOrderByStatusOfStore
     *
     * @param  array  $fillter
     * @param  int  $storeId
     * @return object
     */
    public function countSubOrderByStatusOfStore($fillter, $storeId);

    /**
     * getItemsOfSubOrder
     *
     * @param  int  $subOrderId
     * @return object
     */
    public function getItemsOfSubOrder($subOrderId);

    /**
     * getDateExportSubOrder
     *
     * @param  array  $fillter
     * @param  int  $storeId
     * @return object
     */
    public function getDateExportSubOrder($fillter, $storeId);


    /**
     * @param  int  $orderId
     * @return object
     */
    public function getOrderPaymentById($orderId);

    /**
     * Get total revenue each store.
     *
     * @return Builder|Model|object
     */
    public function getTotalRevenueEachStore();

    /**
     * Get total revenue of store.
     *
     * @param  int  $storeId
     * @return Builder|Model|object
     */
    public function getTotalRevenueOfStore(int $storeId);

    /**
     * Get total order.
     *
     * @param  int  $storeId
     * @param  array  $orderStatusArr
     * @return int
     */
    public function getTotalOrderWithStatus(int $storeId, array $orderStatusArr);

    /**
     * Get order by store.
     *
     * @param  int  $storeId
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  int  $type
     * @return object
     */
    public function getAllOrderByStore($startDate, $endDate, $type, $storeId = null);

    /**
     * Get order list.
     *
     * @param array $condition
     * @param $columns
     * @param bool $isPaginate
     * @return LengthAwarePaginator
     */
    public function getOrderList(array $condition, $columns = ['*'], $isPaginate = true);

    /**
     * Get quantity each status.
     *
     * @param array $condition
     * @return Builder|Model|object
     */
    public function getQuantityEachStatus(array $condition);

    /**
     * Get order detail in CMS.
     *
     * @param int $subOrderId
     * @param array $columns
     * @return mixed
     */
    public function getOrderDetail(int $subOrderId, $columns = ['*']);

    /**
     * update status receive order.
     *
     * @param int $orderId
     * @return bool
     */
    public function confirmOrder(int $orderId): bool;

    /**
     * get detail suborder site user.
     *
     * @param int $subOrderId
     * @return object
     */
    public function getDetailOrderSiteUser($subOrderId);

    /**
     * get builder list order user.
     *
     * @param array $request
     * @param int $customerId
     * @return object
     */
    public function getBuilderListOrderByCustomer($request, $customerId);

    /**
     * get list order user paginate.
     *
     * @param array $request
     * @param int $customerId
     * @return object
     */
    public function getListOrderByCustomer($request, $customerId);

    /**
     * get all list order user.
     *
     * @param array $request
     * @param int $customerId
     * @return object
     */
    public function getAllOrderByCustomer($request, $customerId);

    /**
     * statistic revenue orders in day
     *
     * @param string|null $date
     * @param int|null $storeId
     * @param bool $getAll
     * @return object
     */
    public function statisticRevenueOrderDaily($date = null, $storeId = null, $getAll = false);

    /**
     * statistic revenue age in day
     *
     * @param string|null $date
     * @return object
     */
    public function statisticRevenueAgeDaily($date = null);


    /**
     *
     * @param  array $orderIds
     * @return any
     */
    public function deleteSubOrderByOrderIds($orderIds);
}
