<?php

use App\Enums\EnumLiveStream;
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
        Schema::create('dtb_live_streams', function (Blueprint $table) {
            $table->id();
            $table->datetime('start_time');
            $table->tinyInteger('status')
                ->default(EnumLiveStream::STATUS_NOT_START)
                ->comment("0:not start, 1:playing, 2:end");
            $table->integer('store_id');
            $table->string('channel_name')->nullable();
            $table->string('token')->nullable();
            $table->string('image')->nullable();
            $table->integer('staff_id')->nullable();
            $table->string('title');
            $table->string('url_video')->nullable();
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
        Schema::dropIfExists('dtb_live_streams');
    }
};
