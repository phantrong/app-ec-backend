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
            $table->string('image_front_id')->nullable()->after('image_card_first');
            $table->string('image_back_id')->nullable()->after('image_card_first');
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
            $table->dropColumn('image_front_id');
            $table->dropColumn('image_back_id');
        });
    }
};
