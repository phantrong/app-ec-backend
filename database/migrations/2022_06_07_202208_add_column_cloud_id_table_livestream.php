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
            $table->longText('resource_id_cloud')->nullable();
            $table->string('start_id_cloud')->nullable();
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
            $table->dropColumn('resource_id_cloud');
            $table->dropColumn('start_id_cloud');
        });
    }
};
