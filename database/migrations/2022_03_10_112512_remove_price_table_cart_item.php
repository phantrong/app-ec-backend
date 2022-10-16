<?php

use App\Models\ProductClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (Schema::hasColumn('dtb_cart_items', 'price')) {
            Schema::table('dtb_cart_items', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }

        if (Schema::hasColumn('dtb_product_classes', 'discount')) {
            Schema::table('dtb_product_classes', function (Blueprint $table) {
                $table->dropColumn('discount');
            });
        }

        Schema::table('dtb_product_classes', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->after('price');
        });

        ProductClass::join('dtb_product_classes as temp', 'temp.id', '=', 'dtb_product_classes.id')
            ->update(['dtb_product_classes.discount' => DB::raw("temp.price")]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
