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
        Schema::table('dtb_booking_customers', function (Blueprint $table) {
            $table->tinyInteger('is_called_video')
                ->default(0)
                ->comment('0: false, 1: true')
                ->after('video_call_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtbbooking_customers', function (Blueprint $table) {
            $table->dropColumn('is_called_video');
        });
    }
};
