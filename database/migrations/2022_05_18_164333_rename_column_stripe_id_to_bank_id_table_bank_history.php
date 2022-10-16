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
        Schema::table('dtb_bank_histories', function (Blueprint $table) {
            $table->dropColumn('stripe_id');
            $table->dropColumn('branch_name');
            $table->dropColumn('name');
            $table->integer('branch_id')->after('id');
            $table->integer('bank_id')->after('id');
            $table->string('external_account_id')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_bank_histories', function (Blueprint $table) {
            $table->integer('stripe_id');
            $table->string('name');
            $table->string('branch_name');
            $table->dropColumn('bank_id');
            $table->dropColumn('branch_id');
            $table->dropColumn('external_account_id');
        });
    }
};
