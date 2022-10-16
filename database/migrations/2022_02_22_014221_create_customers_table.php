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
        Schema::create('dtb_customers', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status');
            $table->string('email');
            $table->string('name');
            $table->string('surname');
            $table->string('name_furigana');
            $table->string('surname_furigana');
            $table->string('avatar')->nullable();
            $table->string('password');
            $table->string('phone');
            $table->tinyInteger('gender');
            $table->date('birthday');
            $table->string('verify_content')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('province_name')->nullable();
            $table->string('city')->nullable();
            $table->string('place')->nullable();
            $table->string('home_address')->nullable();
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
        Schema::dropIfExists('dtb_customers');
    }
};
