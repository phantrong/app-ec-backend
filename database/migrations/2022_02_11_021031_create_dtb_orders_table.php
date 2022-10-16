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
        Schema::create('dtb_orders', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status')->default(1);
            $table->decimal('total', 12, 2);
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('total_payment', 12, 2);
            $table->timestamp('ordered_at')->nullable();
            $table->integer('customer_id')->nullable()->unsigned();
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
        Schema::dropIfExists('dtb_orders');
    }
};
