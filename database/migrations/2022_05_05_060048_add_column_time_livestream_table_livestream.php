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
        Schema::table('dtb_live_streams', function (Blueprint $table) {
            $table->dateTime('end_time_actual')->nullable()->after('start_time');
            $table->dateTime('start_time_actual')->nullable()->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_live_streams', function (Blueprint $table) {
            $table->dropColumn('start_time_actual');
            $table->dropColumn('end_time_actual');
        });
    }
};
