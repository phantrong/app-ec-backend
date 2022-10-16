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
            $table->dropColumn('video_call_type');
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
            $table->tinyInteger('video_call_type')
                ->nullable()
                ->comment('1: Public, 2: Private');
        });
    }
};
