<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dtb_stores', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
            $table->string('work_day')->nullable()->change();
            $table->string('date_start')->nullable()->change();
            $table->string('company');
            $table->string('postal_code');
            $table->string('city');
            $table->string('place');
            $table->string('fax')->nullable();
            $table->string('link')->nullable();
            $table->decimal('commission', 12, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_stores', function (Blueprint $table) {
            $table->string('code');
            $table->string('work_day');
            $table->string('date_start');
            $table->dropColumn('company');
            $table->dropColumn('city');
            $table->dropColumn('place');
            $table->dropColumn('fax');
            $table->dropColumn('postal_code');
            $table->dropColumn('link');
            $table->dropColumn('commission');
        });
    }
};
