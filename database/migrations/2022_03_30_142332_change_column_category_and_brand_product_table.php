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
        Schema::table('dtb_products', function (Blueprint $table) {
            $table->integer('category_id')->nullable()->change();
            $table->integer('brand_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_products', function (Blueprint $table) {
            $table->integer('category_id');
            $table->integer('brand_id');
        });
    }
};
