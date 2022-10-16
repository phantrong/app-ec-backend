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
            $table->string('address_kana')->after('address')->nullable();
            $table->string('city_kana')->after('address')->nullable();
            $table->string('place_kana')->after('address')->nullable();
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
            $table->dropColumn('address_kana');
            $table->dropColumn('city_kana');
            $table->dropColumn('place_kana');
        });
    }
};
