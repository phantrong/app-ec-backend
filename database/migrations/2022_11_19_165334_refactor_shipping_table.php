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
        if (Schema::hasColumn('dtb_shippings', 'address_01')) {
            Schema::table('dtb_shippings', function (Blueprint $table) {
                $table->dropColumn('address_01');
                $table->dropColumn('address_02');
                $table->dropColumn('address_03');
                $table->dropColumn('address_04');
                $table->string('address');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('dtb_shippings', 'address')) {
            Schema::table('dtb_shippings', function (Blueprint $table) {
                $table->string('address_01');
                $table->string('address_02');
                $table->string('address_03');
                $table->string('address_04');
                $table->dropColumn('address');
            });
        }
    }
};
