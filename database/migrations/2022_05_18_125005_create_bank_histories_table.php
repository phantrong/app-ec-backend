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
        Schema::create('dtb_bank_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('stripe_id');
            $table->integer('store_id');
            $table->string('name');
            $table->string('branch_name');
            $table->tinyInteger('type')->comment('1: individual; 2:company');
            $table->string('bank_number');
            $table->string('customer_name');
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
        Schema::dropIfExists('bank_histories');
    }
};
