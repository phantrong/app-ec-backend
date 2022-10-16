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
        Schema::create('dtb_shippings', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->unsigned();
            $table->string('receiver_name');
            $table->string('receiver_name_furigana');
            $table->string('phone_number', 20);
            $table->string('postal_code', 50);
            $table->string('address_01');
            $table->string('address_02');
            $table->string('address_03');
            $table->string('address_04')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtb_shippings');
    }
};
