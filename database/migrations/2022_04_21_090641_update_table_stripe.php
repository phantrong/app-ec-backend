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
        Schema::table('dtb_stripes', function (Blueprint $table) {
            $table->string('postal_code', 8);
            $table->string('city', 80);
            $table->string('place', 80);
            $table->integer('province_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_stripes', function (Blueprint $table) {
            $table->dropColumn('postal_code');
            $table->dropColumn('city');
            $table->dropColumn('place');
            $table->dropColumn('province_id');
        });
    }
};
