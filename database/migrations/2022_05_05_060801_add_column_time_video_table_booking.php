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
            $table->dropColumn('minute_video');
            $table->dateTime('end_time_actual')->nullable()->after('status');
            $table->dateTime('start_time_actual')->nullable()->after('status');
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
            $table->integer('minute_video')->after('status')->nullable();
            $table->dropColumn('start_time_actual');
            $table->dropColumn('end_time_actual');
        });
    }
};
