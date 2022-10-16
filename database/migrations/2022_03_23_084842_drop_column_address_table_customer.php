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
        Schema::table('dtb_customers', function (Blueprint $table) {
            $table->dropColumn('province_name');
            $table->dropColumn('city');
            $table->dropColumn('place');
            $table->dropColumn('home_address');
            $table->dropColumn('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_customers', function (Blueprint $table) {
            $table->string('province_name');
            $table->string('postal_code');
            $table->string('place');
            $table->string('city');
            $table->string('home_address');
        });
    }
};
