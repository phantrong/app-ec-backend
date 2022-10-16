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
            $table->string('storage_image_card_first')->nullable()->after('image_card_second');
            $table->string('storage_image_card_second')->nullable()->after('image_card_second');
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
            $table->dropColumn('storage_image_card_first');
            $table->dropColumn('storage_image_card_second');
        });
    }
};
