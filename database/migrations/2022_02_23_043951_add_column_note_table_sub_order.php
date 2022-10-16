<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('dtb_sub_orders', function (Blueprint $table) {
            $table->string('note', 3000)->nullable()->after('total_payment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_sub_orders', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
