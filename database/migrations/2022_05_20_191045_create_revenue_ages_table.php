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
        Schema::create('dtb_revenue_ages', function (Blueprint $table) {
            $table->id();
            $table->integer('store_id');
            $table->date('date_revenue');
            $table->tinyInteger('age')
                ->comment('0 : from 0 to 9; 1: from 10 to 19 ...');
            $table->decimal('revenue', 12, 2);
            $table->integer('total_order');
            $table->datetime('create_at');
            $table->datetime('update_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtb_revenue_ages');
    }
};
