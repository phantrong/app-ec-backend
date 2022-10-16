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
        Schema::create('dtb_sub_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->tinyInteger('status')->default(1);
            $table->integer('order_id')->unsigned();
            $table->integer('store_id')->unsigned();
            $table->decimal('total', 12, 2);
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('total_payment', 12, 2);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
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
        Schema::dropIfExists('dtb_sub_orders');
    }
};
