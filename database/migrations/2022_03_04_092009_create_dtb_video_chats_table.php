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
        Schema::create('dtb_video_chats', function (Blueprint $table) {
            $table->id();
            $table->integer('calendar_staff_id')->unsigned();
            $table->integer('user_id');
            $table->tinyInteger('type')->comment('1: Staff, 2: Customer');
            $table->text('comment');
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
        Schema::dropIfExists('dtb_video_chats');
    }
};
