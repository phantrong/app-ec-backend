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
        if (!Schema::hasColumn('dtb_orders', 'stripe_session_id')) {
            Schema::table('dtb_orders', function (Blueprint $table) {
                $table->string('stripe_session_id')->nullable()->after('customer_id');
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
            $table->dropColumn('stripe_session_id');
        });
    }
};
