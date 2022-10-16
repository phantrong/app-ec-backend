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
        Schema::create('dtb_revenue_products', function (Blueprint $table) {
            $table->id();
            $table->date('date_revenue');
            $table->integer('product_id');
            $table->decimal('revenue', 12);
            $table->integer('total_order');
            $table->integer('total_product');
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
        Schema::dropIfExists('dtb_revenue_products');
    }
};
