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
        DB::statement('ALTER TABLE mtb_categories ADD FULLTEXT `search_name_categories` (`name`)');
        DB::statement('ALTER TABLE mtb_categories ENGINE = MyISAM');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE mtb_categories DROP INDEX search_name_categories');
        DB::statement('ALTER TABLE mtb_categories ENGINE = InnoDB');
    }
};
