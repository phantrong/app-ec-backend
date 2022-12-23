<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RefreshData extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('dtb_product_medias')->truncate();
        DB::table('dtb_products')->truncate();

        DB::table('dtb_shippings')->truncate();
        DB::table('dtb_carts')->truncate();
        DB::table('dtb_cart_items')->truncate();
        DB::table('dtb_order_items')->truncate();
        DB::table('dtb_orders')->truncate();
        DB::table('dtb_sub_orders')->truncate();

        $this->call(ProductsSeeder::class);
    }
}
