<?php

namespace App\Repositories\SubOrder;

use App\Enums\EnumCustomer;
use App\Enums\EnumOrder;
use App\Enums\EnumSubOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductMedia;
use App\Models\Province;
use App\Models\Shipping;
use App\Models\Store;
use App\Models\SubOrder;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SubOrderRepository extends BaseRepository implements SubOrderRepositoryInterface
{
    protected int $perPage = 10;

    const PER_PAGE_IN_CMS = 10;

    public function getModel()
    {
        return SubOrder::class;
    }

    public function getListSubOrderOfStore($fillter, $storeId)
    {
        $tbSubOrder = SubOrder::getTableName();
        $tbOrder = Order::getTableName();
        $tbShipping = Shipping::getTableName();
        $keyWord = $fillter['keyword'];
        $status = $fillter['status'];
        $dateStart = $fillter['date_start'];
        $dateEnd = $fillter['date_end'];
        return $this->model->selectRaw(
            "$tbSubOrder.id,
            $tbSubOrder.code,
            $tbSubOrder.total_payment,
            $tbSubOrder.status,
            date($tbOrder.ordered_at) as ordered_at,
            $tbShipping.receiver_name"
        )
            ->join($tbShipping, "$tbShipping.order_id", "$tbSubOrder.order_id")
            ->join($tbOrder, "$tbOrder.id", "$tbSubOrder.order_id")
            ->where('store_id', $storeId)
            ->where("$tbOrder.status", '<>', EnumOrder::STATUS_NEW)
            ->where(function ($query) use ($keyWord) {
                return $query->where('receiver_name', 'like', '%' . $keyWord . '%')
                    ->orWhere('code', 'like', '%' . $keyWord . '%');
            })
            ->when($status, function ($query) use ($status, $tbSubOrder) {
                if (gettype($status) == 'array') {
                    return $query->whereIn("$tbSubOrder.status", $status);
                }
                return $query->where("$tbSubOrder.status", $status);
            })
            ->when($dateStart, function ($query) use ($dateStart) {
                return $query->whereDate('ordered_at', '>=', $dateStart);
            })
            ->when($dateEnd, function ($query) use ($dateEnd) {
                return $query->whereDate('ordered_at', '<=', $dateEnd);
            })
            ->withSum(
                'orderItems as quantity',
                'quantity'
            )
            ->orderBy("$tbSubOrder.status")
            ->orderByDesc("$tbOrder.ordered_at")
            ->paginate($fillter['per_page'] ?? $this->perPage);
    }

    public function countSubOrderByStatusOfStore($fillter, $storeId)
    {
        $tbSubOrder = SubOrder::getTableName();
        $tbOrder = Order::getTableName();
        $tbShipping = Shipping::getTableName();
        $keyWord = $fillter['keyword'];
        $arrayStatus = $fillter['arrayStatus'];
        $dateStart = $fillter['date_start'];
        $dateEnd = $fillter['date_end'];

        return $this->model->select("$tbSubOrder.status")
            ->selectRaw('count(*) as count')
            ->join($tbShipping, "$tbShipping.order_id", "$tbSubOrder.order_id")
            ->join($tbOrder, "$tbOrder.id", "$tbSubOrder.order_id")
            ->where('store_id', $storeId)
            ->whereIn("$tbSubOrder.status", $arrayStatus)
            ->where("$tbOrder.status", '<>', EnumOrder::STATUS_NEW)
            ->where(function ($query) use ($keyWord) {
                return $query->where('receiver_name', 'like', '%' . $keyWord . '%')
                    ->orWhere('code', 'like', '%' . $keyWord . '%');
            })
            ->when($dateStart, function ($query) use ($dateStart) {
                return $query->whereDate('ordered_at', '>=', $dateStart);
            })
            ->when($dateEnd, function ($query) use ($dateEnd) {
                return $query->whereDate('ordered_at', '<=', $dateEnd);
            })
            ->groupBy("$tbSubOrder.status")
            ->get();
    }

    public function getItemsOfSubOrder($subOrderId)
    {
        $tbSubOrder = SubOrder::getTableName();

        return $this->model->select(
            "$tbSubOrder.id",
            "$tbSubOrder.order_id",
            "$tbSubOrder.total",
            "$tbSubOrder.discount",
            "$tbSubOrder.total_payment",
            "$tbSubOrder.note",
            "$tbSubOrder.code",
            "$tbSubOrder.status",
        )
            ->with([
                'order:id,ordered_at',
                'order.shipping:order_id,receiver_name,receiver_name_furigana,phone_number,' .
                    'address_01,address_02,address_03,address_04',
                'orderItems:sub_order_id,product_class_id,price,quantity',
                'orderItems.productClass:id,product_id',
                'orderItems.productClass.getProductTypeDeleted:type_name,name',
                'orderItems.productClass.product:id,name',
                'orderItems.productClass.product.productMediasImage'
            ])
            ->where("$tbSubOrder.id", $subOrderId)
            ->first();
    }

    public function getDateExportSubOrder($fillter, $storeId)
    {
        $tbSubOrder = SubOrder::getTableName();
        $tbOrder = Order::getTableName();
        $tbShipping = Shipping::getTableName();

        $keyWord = $fillter['keyword'];
        $status = $fillter['status'];
        $dateStart = $fillter['date_start'];
        $dateEnd = $fillter['date_end'];

        return $this->model->select(
            "$tbSubOrder.id",
            "$tbSubOrder.code",
            "$tbSubOrder.total_payment",
            "$tbSubOrder.status",
            "$tbShipping.receiver_name"
        )
            ->selectRaw("date($tbOrder.ordered_at) as ordered_at")
            ->selectRaw("$tbShipping.phone_number as receiver_phone")
            ->selectRaw('concat(address_01, " ",address_02, " ",address_03, " ",COALESCE(address_04,""))
            as receiver_address')
            ->join($tbShipping, "$tbShipping.order_id", "$tbSubOrder.order_id")
            ->join($tbOrder, "$tbOrder.id", "$tbSubOrder.order_id")
            ->with([
                'orderItems:sub_order_id,product_class_id',
                'orderItems.productClass:id,product_id',
                'orderItems.productClass.productTypeConfigs:type_name,name',
                'orderItems.productClass.product:id,name'
            ])
            ->where('store_id', $storeId)
            ->where("$tbOrder.status", '<>', EnumOrder::STATUS_NEW)
            ->where(function ($query) use ($keyWord) {
                return $query->where('receiver_name', 'like', '%' . $keyWord . '%')
                    ->orWhere('code', 'like', '%' . $keyWord . '%');
            })
            ->when($status, function ($query) use ($status, $tbSubOrder) {
                if (gettype($status) == 'array') {
                    return $query->whereIn("$tbSubOrder.status", $status);
                }
                return $query->where("$tbSubOrder.status", $status);
            })
            ->when($dateStart, function ($query) use ($dateStart) {
                return $query->whereDate('ordered_at', '>=', $dateStart);
            })
            ->when($dateEnd, function ($query) use ($dateEnd) {
                return $query->whereDate('ordered_at', '<=', $dateEnd);
            })
            ->withSum('orderItems as quantity', 'quantity')
            ->orderBy("$tbSubOrder.status")
            ->orderByDesc("$tbOrder.ordered_at")
            ->get();
    }

    public function getOrderPaymentById($orderId)
    {
        $tblSubOrder = SubOrder::getTableName();

        return $this->model->with([
            'store',
            'orderItems.productClass',
            'orderItems.product.productMediasImage',
        ])
            ->where("$tblSubOrder.order_id", $orderId)
            ->get();
    }

    public function getBuilderListOrderByCustomer($request, $customerId)
    {
        $tableOrder = Order::getTableName();
        $tableSubOrder = SubOrder::getTableName();
        $status = $request['status'] ?? null;
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;
        return $this->model->select(
            "$tableSubOrder.id",
            "$tableSubOrder.code",
            "$tableSubOrder.status",
            "$tableSubOrder.total",
            "$tableSubOrder.discount",
            "$tableSubOrder.created_at"
        )
            ->join($tableOrder, "$tableOrder.id", '=', "$tableSubOrder.order_id")
            ->where("$tableOrder.customer_id", $customerId)
            ->whereNull("$tableOrder.deleted_at")
            ->whereIn("$tableSubOrder.status", EnumSubOrder::STATUS)
            // ->where("$tableOrder.status", '<>', EnumOrder::STATUS_NEW)
            ->when($status, function ($query) use ($tableSubOrder, $status) {
                return $query->where("$tableSubOrder.status", $status);
            })
            ->when($startDate, function ($query) use ($startDate, $tableSubOrder) {
                return $query->whereDate("$tableSubOrder.created_at", ">=", $startDate);
            })
            ->when($endDate, function ($query) use ($endDate, $tableSubOrder) {
                return $query->whereDate("$tableSubOrder.created_at", "<=", $endDate);
            })
            ->with([
                'orderItems:id,sub_order_id,product_id',
                'orderItems.product:id,name',
            ])
            ->withSum('orderItems as total_product', 'quantity')
            ->orderBy("$tableSubOrder.status")
            ->orderByDesc("$tableSubOrder.created_at");
    }

    public function getListOrderByCustomer($request, $customerId)
    {
        $perPage = $request['per_page'] ?? $this->perPage;
        return $this->getBuilderListOrderByCustomer($request, $customerId)->paginate($perPage);
    }

    public function getAllOrderByCustomer($request, $customerId)
    {
        return $this->getBuilderListOrderByCustomer($request, $customerId)->get();
    }

    public function getDetailSubOrder($subOrderId, array $subOrderStatusArr = [])
    {
        $tableSubOrder = SubOrder::getTableName();
        $tableOrder = Order::getTableName();
        $tableStore = Store::getTableName();
        $tableShipping = Shipping::getTableName();

        return $this->model->select([
            "$tableSubOrder.id",
            "$tableSubOrder.code",
            "$tableSubOrder.status",
            "$tableSubOrder.order_id",
            "$tableStore.avatar",
            "$tableStore.id as store_id",
            "$tableStore.name as store_name",
            "$tableShipping.receiver_name",
            "$tableShipping.receiver_name_furigana",
            "$tableShipping.phone_number",
            "$tableShipping.address_01",
            "$tableShipping.address_02",
            "$tableShipping.address_03",
            "$tableShipping.address_04",
            "$tableSubOrder.total_payment",
            "$tableOrder.id as order_id",
            "$tableOrder.customer_id",
        ])
            ->join($tableOrder, "$tableSubOrder.order_id", '=', "$tableOrder.id")
            ->join($tableStore, "$tableStore.id", '=', "$tableSubOrder.store_id")
            ->join($tableShipping, "$tableShipping.order_id", '=', "$tableOrder.id")
            ->whereNull("$tableOrder.deleted_at")
            ->where("$tableSubOrder.id", $subOrderId)
            ->when($subOrderStatusArr, function ($query) use ($tableSubOrder, $subOrderStatusArr) {
                $query->whereIn("$tableSubOrder.status", $subOrderStatusArr);
            })
            ->with([
                'orderItems:sub_order_id,product_class_id,quantity,price',
                'orderItems.productClass:id,product_id',
                'orderItems.productClass.product:id,name',
                'orderItems.productClass.product.productMediasImage',
                'orderItems.productClass.getProductTypeDeleted:type_name',
                'order.shipping:id,order_id,email'
            ])
            ->first();
    }

    public function getDetailOrderSiteUser($subOrderId)
    {
        $tableSubOrder = SubOrder::getTableName();
        return $this->model->select(
            'id',
            'status',
            'order_id',
            'store_id',
            'sub_order_code',
            'code',
            'total',
            'discount',
            'total_payment'
        )
            ->where("$tableSubOrder.id", $subOrderId)
            ->with([
                'store:id,name,avatar',
                'orderItems:sub_order_id,product_id,quantity,price',
                'orderItems.product:id,name',
                'orderItems.product.productMediasImage',
                'order.shipping'
            ])
            ->first();
    }

    // get all sub order by store
    public function getAllOrderByStore($startDate, $endDate, $type, $storeId = null)
    {
        $typeDay = EnumSubOrder::UNIT_DAY;
        $typeMonth = EnumSubOrder::UNIT_MONTH;
        $typeYear = EnumSubOrder::UNIT_YEAR;
        $tableSubOrder = SubOrder::getTableName();
        $tableOrder = Order::getTableName();
        $tableCustomer = Customer::getTableName();
        return $this->model->select(
            "$tableSubOrder.total_payment",
            DB::raw("$tableSubOrder.total_payment * (1 - $tableSubOrder.commission) as revenue_actual"),
            DB::raw("CASE WHEN $type = $typeDay THEN DATE(completed_at)
            WHEN $type = $typeMonth THEN DATE_FORMAT(completed_at, '%Y-%m')
            WHEN $type = $typeYear THEN YEAR(completed_at) END as date"),
            "$tableOrder.customer_id",
            "$tableCustomer.gender"
        )
            ->join($tableOrder, "$tableOrder.id", '=', "$tableSubOrder.order_id")
            ->leftJoin($tableCustomer, "$tableCustomer.id", '=', "$tableOrder.customer_id")
            ->when($storeId, function ($query) use ($storeId, $tableSubOrder) {
                return $query->where("$tableSubOrder.store_id", $storeId);
            })
            ->where("$tableSubOrder.status", EnumSubOrder::STATUS_SHIPPED)
            ->whereNull("$tableOrder.deleted_at")
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('completed_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('completed_at', '<=', $endDate);
            })
            ->orderBy('date')
            ->get();
    }

    /**
     * Get total revenue each store.
     *
     * @return Builder[]|object
     */
    public function getTotalRevenueEachStore()
    {
        $tableSubOrder = SubOrder::getTableName();
        $tableOrder = Order::getTableName();

        return SubOrder::query()
            ->selectRaw("
                store_id,
                SUM($tableSubOrder.total_payment) as total_revenue,
                SUM($tableSubOrder.total_payment * (1 - commission)) as revenue_admin
            ")
            ->join($tableOrder, "$tableOrder.id", '=', "$tableSubOrder.order_id")
            ->whereNotNull("$tableSubOrder.completed_at")
            ->whereNull("$tableOrder.deleted_at")
            ->groupBy("$tableSubOrder.store_id")
            ->get();
    }

    /**
     * Get total revenue of store.
     *
     * @param int $storeId
     * @return Builder|Model|object
     */
    public function getTotalRevenueOfStore(int $storeId)
    {
        $tableSubOrder = SubOrder::getTableName();
        $tableOrder = Order::getTableName();

        return $this->model
            ->selectRaw("
                SUM($tableSubOrder.total_payment) as total_revenue,
                SUM($tableSubOrder.total_payment * (1 - commission)) as revenue_admin
            ")
            ->join($tableOrder, "$tableOrder.id", '=', "$tableSubOrder.order_id")
            ->where('store_id', '=', $storeId)
            ->whereNull("$tableOrder.deleted_at")
            ->groupBy("$tableSubOrder.store_id")
            ->first();
    }

    /**
     * Get total order.
     *
     * @param int $storeId
     * @param array $orderStatusArr
     * @return int
     */
    public function getTotalOrderWithStatus(int $storeId, array $orderStatusArr)
    {
        $tableSubOrder = SubOrder::getTableName();
        $tableOrder = Order::getTableName();

        return $this->model
            ->join($tableOrder, 'order_id', '=', "$tableOrder.id")
            ->whereNull("$tableOrder.deleted_at")
            ->where('store_id', '=', $storeId)
            ->whereIn("$tableSubOrder.status", $orderStatusArr)
            ->count("$tableSubOrder.id");
    }

    /**
     * Get order list.
     *
     * @param array $condition
     * @param $columns
     * @return LengthAwarePaginator
     */
    public function getOrderList(array $condition, $columns = ['*'], $isPaginate = true)
    {
        $perPage = Arr::get($condition, 'per_page', self::PER_PAGE_IN_CMS);
        $page = Arr::get($condition, 'page', 1);

        $tblSubOrder = $this->model->getTableName();
        $tblOrder = Order::getTableName();
        $tblStore = Store::getTableName();
        $tblShipping = Shipping::getTableName();

        $listOrder = $this->model
            ->select($columns)
            ->selectRaw("
                $tblSubOrder.total_payment * $tblSubOrder.commission AS revenue_admin
            ")
            ->join($tblOrder, 'order_id', '=', "$tblOrder.id")
            ->join($tblStore, 'store_id', '=', "$tblStore.id")
            ->join($tblShipping, "$tblOrder.id", '=', "$tblShipping.order_id")
            ->whereNull("$tblOrder.deleted_at")
            ->whereNull("$tblStore.deleted_at")
            ->whereIn("$tblOrder.status", EnumOrder::ARRAY_STATUS_SUCCESS)
            ->when(isset($condition['key_word']), function ($query) use ($tblSubOrder, $condition, $tblStore) {
                return $query->where(function ($query) use ($tblSubOrder, $condition, $tblStore) {
                    return $query->where("$tblSubOrder.code", 'like', "%{$condition['key_word']}%")
                        ->orWhere("$tblStore.name", 'like', "%{$condition['key_word']}%");
                });
            })
            ->when(
                isset($condition['status']) && $condition['status'],
                function ($query) use ($tblSubOrder, $condition) {
                    return $query->where("$tblSubOrder.status", $condition['status']);
                }
            )
            ->when(isset($condition['start_date']), function ($query) use ($tblSubOrder, $condition) {
                return $query->whereDate(
                    "$tblSubOrder.created_at",
                    ">=",
                    $condition['start_date']
                );
            })
            ->when(isset($condition['end_date']), function ($query) use ($tblSubOrder, $condition) {
                return $query->whereDate(
                    "$tblSubOrder.created_at",
                    "<=",
                    $condition['end_date']
                );
            })
            ->withSum('orderItems as total_product', 'quantity')
            ->orderByDesc("$tblSubOrder.created_at");
        if ($isPaginate) {
            return $listOrder->paginate($perPage, $columns, 'page', $page);
        }
        return $listOrder->get();
    }

    /**
     * Get quantity each status.
     *
     * @return Builder|Model|object
     */
    public function getQuantityEachStatus(array $condition)
    {
        $tblSubOrder = $this->model->getTableName();
        $tblOrder = Order::getTableName();
        $tblStore = Store::getTableName();
        return $this->model
            ->selectRaw("$tblSubOrder.status, COUNT($tblSubOrder.id) AS quantity")
            ->join($tblOrder, 'order_id', '=', "$tblOrder.id")
            ->join($tblStore, 'store_id', '=', "$tblStore.id")
            ->when(isset($condition['key_word']), function ($query) use ($tblSubOrder, $condition, $tblStore) {
                return $query->where(function ($query) use ($tblSubOrder, $condition, $tblStore) {
                    return $query->where("$tblSubOrder.code", 'like', "%{$condition['key_word']}%")
                        ->orWhere("$tblStore.name", 'like', "%{$condition['key_word']}%");
                });
            })
            ->when(isset($condition['start_date']), function ($query) use ($tblSubOrder, $condition) {
                return $query->whereDate(
                    "$tblSubOrder.created_at",
                    ">=",
                    $condition['start_date']
                );
            })
            ->when(isset($condition['end_date']), function ($query) use ($tblSubOrder, $condition) {
                return $query->whereDate(
                    "$tblSubOrder.created_at",
                    "<=",
                    $condition['end_date']
                );
            })
            ->whereNull("$tblOrder.deleted_at")
            ->whereIn("$tblOrder.status", EnumOrder::ARRAY_STATUS_SUCCESS)
            ->groupBy(["$tblSubOrder.status"])
            ->orderBy("$tblSubOrder.status")
            ->get();
    }

    /**
     * Get order detail in CMS.
     *
     * @param int $subOrderId
     * @param array $columns
     * @return mixed
     */
    public function getOrderDetail(int $subOrderId, $columns = ['*'])
    {
        $tblSubOrder = SubOrder::getTableName();
        $tblOrder = Order::getTableName();

        return $this->model
            ->join($tblOrder, 'order_id', '=', "$tblOrder.id")
            ->where("$tblSubOrder.id", $subOrderId)
            ->with([
                'orderItems:sub_order_id,product_class_id,quantity,price',
                'orderItems.productClass:id,product_id',
                'orderItems.productClass.product:id,name',
                'orderItems.productClass.product.productMediasImage',
                'orderItems.productClass.productTypeConfigs:type_name'
            ])
            ->get($columns);
    }

    /**
     * update status receive order.
     *
     * @param int $orderId
     * @return bool
     */
    public function confirmOrder(int $orderId): bool
    {
        $arrayStatus = [EnumSubOrder::STATUS_SHIPPED, EnumSubOrder::STATUS_SHIPPING];
        return $this->model
            ->where('id', $orderId)
            ->whereIn('status', $arrayStatus)
            ->update([
                'status' => EnumSubOrder::STATUS_SHIPPED,
                'completed_at' => now()->format('Y-m-d H:i:s')
            ]);
    }

    public function getInfoOrderExportPdf($subOrderId)
    {
        $tblStore = Store::getTableName();
        $tblSubOrder = SubOrder::getTableName();
        $tblProvince = Province::getTableName();
        return $this->model
            ->selectRaw(
                "$tblSubOrder.id,
                $tblSubOrder.sub_order_code,
                $tblSubOrder.code,
                $tblSubOrder.total_payment,
                $tblSubOrder.commission,
                $tblStore.company,
                $tblStore.phone,
                $tblStore.name as store_name,
                CONCAT($tblProvince.name,$tblStore.city,$tblStore.place,COALESCE($tblStore.address,'')) as address,
                $tblStore.fax"
            )
            ->join($tblStore, "$tblStore.id", '=', "$tblSubOrder.store_id")
            ->join($tblProvince, "$tblProvince.id", "$tblStore.province_id")
            ->where("$tblSubOrder.id", $subOrderId)
            ->with([
                'orderItems:sub_order_id,product_class_id,quantity,price',
                'orderItems.productClass:id,product_id',
                'orderItems.productClass.product:id,name',
                'orderItems.productClass.productTypeConfigs:type_name'
            ])
            ->first();
    }

    public function statisticRevenueAgeDaily($date = null, $storeId = null)
    {
        $date = $date ?: now()->subDay(1)->format('Y-m-d');
        $now = now()->format('Y-m-d H:i:s');
        $tableSubOrder = SubOrder::getTableName();
        $tableOrder = Order::getTableName();
        $tableCustomer = Customer::getTableName();
        return $this->model->selectRaw(
            "'$date' as date_revenue,
            $tableSubOrder.store_id,
            COUNT($tableSubOrder.id) as total_order,
            SUM($tableSubOrder.total_payment) as revenue,
            TIMESTAMPDIFF(year, birthday, now()) DIV 10 as age,
            '$now' as create_at,
            '$now' as update_at"
        )
            ->join($tableOrder, "$tableOrder.id", '=', "$tableSubOrder.order_id")
            ->join($tableCustomer, "$tableCustomer.id", '=', "$tableOrder.customer_id")
            ->when($storeId, function ($query) use ($tableSubOrder, $storeId) {
                return $query->where("$tableSubOrder.store_id", $storeId);
            })
            ->where("$tableSubOrder.status", EnumSubOrder::STATUS_SHIPPED)
            ->whereNotNull("$tableOrder.customer_id")
            ->whereNull("$tableOrder.deleted_at")
            ->whereDate("$tableSubOrder.completed_at", $date)
            ->groupBy('store_id')
            ->groupBy('age')
            ->orderBy('age')
            ->get();
    }

    public function statisticRevenueOrderDaily($date = null, $storeId = null, $getAll = false)
    {
        $date = $date ?: now()->subDay(1)->format('Y-m-d');
        $tblOrder = Order::getTableName();
        $tblSubOrder = SubOrder::getTableName();
        $tblCustomer = Customer::getTableName();
        $now = now()->format('Y-m-d H:i:s');
        $genderMale = EnumCustomer::GENDER_MALE;
        $genderFeMale = EnumCustomer::GENDER_FEMALE;
        $genderUnKnow = EnumCustomer::GENDER_UN_KNOWN;
        $orders = SubOrder::selectRaw(
            "DATE(completed_at) as revenue_date,
                COUNT($tblSubOrder.id) as total_order,
                COUNT(case when gender = $genderMale then 1 else null end) as customer_male,
                COUNT(case when gender = $genderFeMale then 1 else null end) as customer_female,
                COUNT(case when gender = $genderUnKnow then 1 else null end) as customer_unknown,
                COUNT(case when gender is null then 1 else null end) as customer_not_login ,
                SUM($tblSubOrder.total_payment) as revenue,
                SUM($tblSubOrder.total_payment * (1 - commission)) as revenue_actual,
                '$now' as create_at,
                '$now' as update_at"
        )
            ->when($getAll || $storeId, function ($query) use ($tblSubOrder) {
                return $query->selectRaw("$tblSubOrder.store_id");
            })
            ->join($tblOrder, "$tblOrder.id", '=', "$tblSubOrder.order_id")
            ->leftjoin($tblCustomer, "$tblCustomer.id", '=', "$tblOrder.customer_id")
            ->whereNull("$tblOrder.deleted_at")
            ->whereDate("$tblSubOrder.completed_at", "$date")
            ->when($storeId, function ($query) use ($tblSubOrder, $storeId) {
                return $query->where("$tblSubOrder.store_id", $storeId)
                    ->groupBy('store_id');
            })
            ->when($getAll, function ($query) {
                return $query->groupBy('store_id');
            })
            ->groupBy('revenue_date');
        if ($getAll) {
            return $orders->get();
        }
        return $orders->first();
    }

    public function deleteSubOrderByOrderIds($orderIds)
    {
        return SubOrder::whereIn('order_id', $orderIds)
            ->delete();
    }
}
