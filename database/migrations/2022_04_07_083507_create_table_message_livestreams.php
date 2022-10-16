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
        Schema::create('dtb_message_livestreams', function (Blueprint $table) {
            $table->id();
            $table->integer('livestream_id');
            $table->tinyInteger('type')->comment("1:customer; 2:store");
            $table->integer('user_id')->nullable();
            $table->text('content');
            $table->softDeletes();
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
        Schema::dropIfExists('dtb_message_livestreams');
    }
};
