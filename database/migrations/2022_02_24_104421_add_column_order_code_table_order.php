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
        if (!Schema::hasColumn('dtb_orders', 'order_code')) {
            Schema::table('dtb_orders', function (Blueprint $table) {
                $table->string('order_code', 50)->after('id');
            });
        }

        if (!Schema::hasColumn('dtb_sub_orders', 'sub_order_code')) {
            Schema::table('dtb_sub_orders', function (Blueprint $table) {
                $table->string('sub_order_code', 200)->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_orders', function (Blueprint $table) {
            $table->dropColumn('order_code');
        });
        Schema::table('dtb_sub_orders', function (Blueprint $table) {
            $table->dropColumn('sub_order_code');
        });
    }
};
