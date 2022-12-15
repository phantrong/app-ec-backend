<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE dtb_products ADD FULLTEXT `search_name_products` (`name`)');
        DB::statement('ALTER TABLE dtb_products ADD FULLTEXT `search_description_products` (`description`)');
        DB::statement('ALTER TABLE dtb_products ENGINE = MyISAM');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE dtb_products DROP INDEX search_name_products');
        DB::statement('ALTER TABLE dtb_products DROP INDEX search_description_products');
        DB::statement('ALTER TABLE dtb_products ENGINE = InnoDB');
    }
};
