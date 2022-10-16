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
        Schema::create('dtb_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_payout_id', '60');
            $table->string('currency');
            $table->string('method');
            $table->decimal('amount');
            $table->string('source_type');
            $table->string('status');
            $table->string('type');
            $table->integer('store_id')->nullable();
            $table->string('stripe_bank_id');
            $table->tinyInteger('automatic')->default(0)->comment('1: true; 0:false');
            $table->integer('arrival_date');
            $table->integer('created');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payouts');
    }
};
