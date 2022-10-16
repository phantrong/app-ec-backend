<?php

namespace App\Console\Commands;

use App\Enums\EnumOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductClass;
use App\Models\SubOrder;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelOrder extends Command
{
    const TIME_EXPIRED = 1441;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $orderExpired = $this->getOrderExpired();
            $orderIds = $orderExpired->get()->pluck('id');
            if ($orderIds) {
                $this->updateQuantityProductOrderExpired($orderIds);
                $this->deleteSubOrderByOrderIds($orderIds);
                $orderExpired->delete();
                Log::channel('cancel_order')->info('cancel: ' . implode(",", $orderIds));
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('cancel_order')->info('======= start cancel_order error =======');
            Log::channel('cancel_order')->error($e);
            Log::channel('cancel_order')->info('======= end cancel_order error ============');
        }
    }

    public function getOrderExpired()
    {
        $now = now()->subMinute(self::TIME_EXPIRED)->format('Y-m-d H:i:s');
        return Order::where('status', EnumOrder::STATUS_NEW)
            ->where('created_at', '<=', $now);
    }

    public function updateQuantityProductOrderExpired($orderIds)
    {
        $tblSubOrder = SubOrder::getTableName();
        $tblProductClass = ProductClass::getTableName();
        $tblOrderItem = OrderItem::getTableName();
        $tblProductQuantity = OrderItem::selectRaw(
            "product_class_id,
            SUM(quantity) as quantity"
        )
            ->join($tblSubOrder, "$tblSubOrder.id", '=', "$tblOrderItem.sub_order_id")
            ->whereIn("$tblSubOrder.order_id", $orderIds)
            ->groupBy('product_class_id');
        return ProductClass::joinSub($tblProductQuantity, 'product_quantity', function ($join) use ($tblProductClass) {
            $join->on("$tblProductClass.id", '=', 'product_quantity.product_class_id');
        })
            ->update([
                "stock" => DB::raw('stock + quantity')
            ]);
    }

    public function deleteSubOrderByOrderIds($orderIds)
    {
        return SubOrder::whereIn('order_id', $orderIds)
            ->delete();
    }
}
