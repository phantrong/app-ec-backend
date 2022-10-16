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
        $tblBrand = Brand::getTableName();
        $tblStaff = Staff::getTableName();
        $tblStripe = Stripe::getTableName();
        $tblProvince = Province::getTableName();
        $tblStore = Store::getTableName();
        DB::unprepared(
            "ALTER TABLE $tblBrand MODIFY COLUMN category_id bigint;
            ALTER TABLE $tblStaff MODIFY COLUMN store_id bigint;
            ALTER TABLE $tblStore CHANGE address address varchar(255) AFTER place,
                MODIFY COLUMN date_start date;
            ALTER TABLE  $tblStripe CHANGE address address varchar(255) AFTER province_id,
                CHANGE address_kana address_kana varchar(255) NULL AFTER address,
                CHANGE place_kana place_kana varchar(255) NULL AFTER address_kana,
                CHANGE city_kana city_kana varchar(255) NULL AFTER place_kana;
            ALTER TABLE $tblProvince ADD COLUMN order_number int DEFAULT 1;
            UPDATE $tblProvince set order_number = id WHERE id >= 1;
            "
        );
    }
}
