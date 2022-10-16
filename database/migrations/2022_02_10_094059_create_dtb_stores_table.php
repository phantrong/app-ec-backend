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
        Schema::create('dtb_stores', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status')->default(1);
            $table->string('code', 100);
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 20);
            $table->date('date_start');
            $table->string('time_start', 5)->nullable();
            $table->string('time_end', 5)->nullable();
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_image')->nullable();
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
        Schema::dropIfExists('dtb_stores');
    }
};
