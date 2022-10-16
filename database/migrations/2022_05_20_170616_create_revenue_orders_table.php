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
        Schema::create('dtb_revenue_orders', function (Blueprint $table) {
            $table->id();
            $table->date('revenue_date');
            $table->integer('store_id');
            $table->integer('total_order')->comment('total order in a day');
            $table->integer('customer_male')->comment('number customer is male');
            $table->integer('customer_female')->comment('number customer is female');
            $table->integer('customer_unknown')->comment('number customer is un known');
            $table->integer('customer_not_login')->comment('number customer not login');
            $table->decimal('revenue', 12)->comment('total money payment');
            $table->decimal('revenue_actual', 12)->comment('revenue decrease commission');
            $table->datetime('create_at');
            $table->datetime('update_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtb_revenue_orders');
    }
};
