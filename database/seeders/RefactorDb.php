<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Province;
use App\Models\Staff;
use App\Models\Store;
use App\Models\Stripe;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RefactorDb extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // stores
        DB::unprepared("
        ALTER TABLE `dtb_stores` DROP `customer_id`, DROP `bank_history_id_current`, DROP `province_id`, DROP `phone`, DROP `work_day`, DROP `date_start`, DROP `time_start`, DROP `time_end`, DROP `link_instagram`, DROP `acc_stripe_id`, DROP `company`, DROP `postal_code`, DROP `city`, DROP `place`, DROP `fax`, DROP `link`, DROP `commission`, DROP `date_applicable_commission`, DROP `date_approved`;");

        // staff
        DB::unprepared("ALTER TABLE `dtb_staffs` DROP `gender`, DROP `address`, DROP `verify_content`;");

        // product
        DB::unprepared("ALTER TABLE `dtb_products` DROP `note`, DROP `property`, DROP `last_status`;
        ALTER TABLE `dtb_products` ADD `price` DECIMAL(12,2) NOT NULL AFTER `description`, ADD `discount` DECIMAL(12,2) NOT NULL AFTER `price`;
        ALTER TABLE `dtb_products` ADD `stock` INT NOT NULL DEFAULT '1000' AFTER `discount`;");

        // customer
        DB::unprepared("ALTER TABLE `dtb_customers` DROP `store_id`, DROP `surname`, DROP `name_furigana`, DROP `surname_furigana`, DROP `status_signup_store`;
        ALTER TABLE `dtb_customers` ADD `address` VARCHAR(2000) NULL AFTER `birthday`;");

        // cart
        DB::unprepared("ALTER TABLE `dtb_cart_items` CHANGE `product_classes_id` `product_id` INT(10) UNSIGNED NOT NULL;");
    }
}
