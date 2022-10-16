<?php

namespace Database\Seeders;

use App\Models\BankHistory;
use App\Models\Booking;
use App\Models\Brand;
use App\Models\CalendarStaff;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\LiveStream;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductClass;
use App\Models\ProductFavorite;
use App\Models\ProductMedia;
use App\Models\Products;
use App\Models\ProductType;
use App\Models\ProductTypeConfig;
use App\Models\RevenueAge;
use App\Models\RevenueOrder;
use App\Models\RevenueProduct;
use App\Models\Shipping;
use App\Models\Staff;
use App\Models\Store;
use App\Models\Stripe;
use App\Models\SubOrder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChangeTypeColumnTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tblBankHistory = BankHistory::getTableName();
        $tblBooking = Booking::getTableName();
        $tblCalendarStaff = CalendarStaff::getTableName();
        $tblCartItem = CartItem::getTableName();
        $tblCart = Cart::getTableName();
        $tblCustomerAddress = CustomerAddress::getTableName();
        $tblCustomer = Customer::getTableName();
        $tblLivestream = LiveStream::getTableName();
        $tblOrderItem = OrderItem::getTableName();
        $tblOrder = Order::getTableName();
        $tblProductClass = ProductClass::getTableName();
        $tblProductFavorite = ProductFavorite::getTableName();
        $tblProductMedia = ProductMedia::getTableName();
        $productTypeConfig = ProductTypeConfig::getTableName();
        $tblProductType = ProductType::getTableName();
        $tblProduct = Products::getTableName();
        $tblRevenueAge = RevenueAge::getTableName();
        $tblRevenueOrder = RevenueOrder::getTableName();
        $tblRevenueProduct = RevenueProduct::getTableName();
        $tblShipping = Shipping::getTableName();
        $tblStaff = Staff::getTableName();
        $tblStore = Store::getTableName();
        $tblStripe = Stripe::getTableName();
        $tblSubOrder = SubOrder::getTableName();
        $tblBrand = Brand::getTableName();

        DB::unprepared(
            "ALTER TABLE $tblBankHistory MODIFY COLUMN bank_id bigint,
                    MODIFY COLUMN branch_id bigint;
                ALTER TABLE $tblBooking MODIFY COLUMN calendar_staff_id bigint,
                    MODIFY COLUMN customer_id bigint;
                ALTER TABLE $tblCalendarStaff MODIFY COLUMN staff_id bigint;
                ALTER TABLE $tblCartItem MODIFY COLUMN cart_id bigint,
                    MODIFY COLUMN product_classes_id bigint;
                ALTER TABLE $tblCart MODIFY COLUMN customer_id bigint;
                ALTER TABLE $tblCustomerAddress MODIFY COLUMN customer_id bigint;
                ALTER TABLE $tblCustomer MODIFY COLUMN store_id bigint;
                ALTER TABLE $tblLivestream MODIFY COLUMN staff_id bigint,
                    MODIFY COLUMN store_id bigint;
                ALTER TABLE $tblOrderItem MODIFY COLUMN product_class_id bigint,
                     MODIFY COLUMN product_id bigint;
                ALTER TABLE $tblOrder MODIFY COLUMN customer_id bigint;
                ALTER TABLE $tblProductClass MODIFY COLUMN product_id bigint;
                ALTER TABLE $tblProductFavorite MODIFY COLUMN customer_id bigint,
                    MODIFY COLUMN product_id bigint;
                ALTER TABLE $tblProductMedia MODIFY COLUMN product_id bigint;
                ALTER TABLE $productTypeConfig MODIFY COLUMN product_id bigint;
                ALTER TABLE $tblProductType MODIFY COLUMN product_type_config_id bigint,
                    MODIFY COLUMN product_class_id bigint;
                ALTER TABLE $tblProduct MODIFY COLUMN store_id bigint,
                    MODIFY COLUMN brand_id bigint,
                    MODIFY COLUMN category_id bigint;
                ALTER TABLE $tblRevenueAge MODIFY COLUMN store_id bigint;
                ALTER TABLE $tblRevenueOrder MODIFY COLUMN store_id bigint;
                ALTER TABLE $tblRevenueProduct MODIFY COLUMN product_id bigint;
                ALTER TABLE $tblShipping MODIFY COLUMN order_id bigint;
                ALTER TABLE $tblStaff MODIFY COLUMN store_id bigint;
                ALTER TABLE $tblStore MODIFY COLUMN customer_id bigint,
                    MODIFY COLUMN bank_history_id_current bigint,
                    MODIFY COLUMN province_id bigint;
                ALTER TABLE $tblStripe MODIFY COLUMN customer_id bigint,
                    MODIFY COLUMN province_id bigint;
                ALTER TABLE $tblSubOrder MODIFY COLUMN store_id bigint,
                    MODIFY COLUMN order_id bigint;
                ALTER TABLE $tblBrand MODIFY COLUMN category_id bigint;
                ALTER TABLE $tblStore CHANGE province_id province_id bigint AFTER postal_code;
                "
        );
    }
}
