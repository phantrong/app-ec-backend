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
        Schema::table('dtb_stores', function (Blueprint $table) {
            $table->renameColumn('bank_id_current', 'bank_history_id_current');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_stores', function (Blueprint $table) {
            $table->renameColumn('bank_history_id_current', 'bank_id_current');
        });
    }
};
