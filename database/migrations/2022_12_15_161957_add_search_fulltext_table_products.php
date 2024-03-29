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
        DB::statement('ALTER TABLE dtb_products ADD FULLTEXT `search_fulltext_products` (`name`, `description`)');
        DB::statement('ALTER TABLE dtb_products ENGINE = MyISAM');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE dtb_products DROP INDEX search_fulltext_products');
        DB::statement('ALTER TABLE dtb_products ENGINE = InnoDB');
    }
};
