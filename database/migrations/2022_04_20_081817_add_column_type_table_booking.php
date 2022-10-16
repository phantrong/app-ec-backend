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
        Schema::table('dtb_bookings', function (Blueprint $table) {
            $table->tinyInteger('video_call_type')
                ->nullable()
                ->comment("1:public; 2:private");
            ;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_bookings', function (Blueprint $table) {
            $table->dropColumn('video_call_type');
        });
    }
};
