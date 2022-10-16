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
        if (!Schema::hasColumn('dtb_stores', 'acc_stripe_id')) {
            Schema::table('dtb_stores', function (Blueprint $table) {
                $table->string('acc_stripe_id', 50)->nullable();
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
        Schema::table('dtb_stores', function (Blueprint $table) {
            $table->dropColumn('acc_stripe_id');
        });
    }
};
