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
            $table->string('channel_name')->nullable()->after('minute_video');
            $table->string('token')->nullable()->after('minute_video');
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
            $table->dropColumn('channel_name');
            $table->dropColumn('token');
        });
    }
};
