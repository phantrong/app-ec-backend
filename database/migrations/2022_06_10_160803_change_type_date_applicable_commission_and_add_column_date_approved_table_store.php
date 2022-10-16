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
            $table->datetime('date_applicable_commission')->nullable()->change();
            $table->datetime('date_approved')->nullable();
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
            $table->date('date_applicable_commission')->nullable()->change();
            $table->dropColumn('date_approved');
        });
    }
};
