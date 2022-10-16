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
        Schema::table('dtb_staffs', function (Blueprint $table) {
            $table->string('phone', 11)
                ->nullable()
                ->after('name');
            $table->tinyInteger('gender')
                ->nullable()
                ->comment('1: female, 2: male')
                ->after('phone');
            $table->text('address')
                ->nullable()
                ->after('gender');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_staffs', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->dropColumn('gender');
            $table->dropColumn('phone');
        });
    }
};
