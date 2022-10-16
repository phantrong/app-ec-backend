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
        Schema::create('dtb_bookings', function (Blueprint $table) {
            $table->id();
            $table->integer('calendar_staff_id')->unsigned();
            $table->tinyInteger('status')->comment('
                1: Pending confirm,
                2: Confirm,
                3: Processing,
                4: Complete,
                5: Cancel,
                6: Cancel force,
            ');
            $table->integer('minute_video')->nullable();
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
        Schema::dropIfExists('dtb_bookings');
    }
};
