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
        Schema::table('dtb_calendar_staff', function (Blueprint $table) {
            $table->dropColumn('is_called_video');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_calendar_staff', function (Blueprint $table) {
            $table->tinyInteger('is_called_video')
                ->default(0)
                ->comment('0: False, 1: True')
                ->after('video_call_type');
        });
    }
};
