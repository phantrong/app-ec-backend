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
        Schema::table('dtb_stripes', function (Blueprint $table) {
            $table->renameColumn('first_name', 'surname');
            $table->renameColumn('last_name', 'name');
            $table->renameColumn('first_name_furigana', 'surname_furigana');
            $table->renameColumn('last_name_furigana', 'name_furigana');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_stripes', function (Blueprint $table) {
            $table->renameColumn('surname', 'first_name');
            $table->renameColumn('name', 'last_name');
            $table->renameColumn('surname_furigana', 'first_name_furigana');
            $table->renameColumn('name_furigana', 'last_name_furigana');
        });
    }
};
