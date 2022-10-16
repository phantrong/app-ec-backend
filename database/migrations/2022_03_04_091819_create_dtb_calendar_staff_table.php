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
        Schema::create('dtb_calendar_staff', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id')->unsigned();
            $table->date('reception_date');
            $table->time('reception_start_time');
            $table->time('reception_end_time');
            $table->tinyInteger('video_call_type')
                ->nullable()
                ->comment('1: Public, 2: Private');
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
        Schema::dropIfExists('dtb_calendar_staff');
    }
};
