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
        Schema::create('dtb_product_classes', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->tinyInteger('status');
            $table->tinyInteger('has_type_config');
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->datetime('sale_from')->nullable();
            $table->datetime('sale_to')->nullable();
            $table->integer('stock');
            $table->integer('sale_limit');
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
        Schema::dropIfExists('dtb_product_classes');
    }
};
