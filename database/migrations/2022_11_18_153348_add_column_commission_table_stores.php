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
        if (!Schema::hasColumn('dtb_stores', 'commission')) {
            Schema::table('dtb_stores', function (Blueprint $table) {
                $table->decimal('commission', 12, 2)->nullable();
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
        if (Schema::hasColumn('dtb_stores', 'commission')) {
            Schema::table('dtb_stores', function (Blueprint $table) {
                $table->dropColumn('commission');
            });
        }
    }
};
