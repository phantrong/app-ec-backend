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
        Schema::create('dtb_order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('sub_order_id')->unsigned();
            $table->integer('product_class_id')->unsigned();
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->integer('cart_item_id')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtb_order_items');
    }
};
