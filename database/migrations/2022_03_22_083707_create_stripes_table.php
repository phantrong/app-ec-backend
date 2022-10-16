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
        Schema::create('dtb_stripes', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->string('person_stripe_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('first_name_furigana');
            $table->string('last_name_furigana');
            $table->tinyInteger('gender');
            $table->date('birthday');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('position');
            $table->tinyInteger('image_type');
            $table->string('image_card_first');
            $table->string('image_card_second');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtb_stripes');
    }
};
