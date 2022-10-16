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
        Schema::create('dtb_customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->string('postal_code');
            $table->string('province_name');
            $table->string('place');
            $table->string('city');
            $table->string('home_address');
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
        Schema::dropIfExists('dtb_customer_addresses');
    }
};
