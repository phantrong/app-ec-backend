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
        Schema::create('dtb_booking_customers', function (Blueprint $table) {
            $table->id();
            $table->integer('calendar_staff_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->tinyInteger('video_call_type')->comment('1: Public, 2: Private');
            $table->text('note')->nullable();
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
        Schema::dropIfExists('dtb_booking_customers');
    }
};
