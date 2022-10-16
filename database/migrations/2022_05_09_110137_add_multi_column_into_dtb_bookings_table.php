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
        Schema::table('dtb_bookings', function (Blueprint $table) {
            $table->integer('customer_id')
                ->unsigned()
                ->nullable()
                ->after('calendar_staff_id');
            $table->text('customer_note')
                ->nullable()
                ->after('status');
            $table->string('token')
                ->nullable()
                ->comment('Agora token')
                ->change();
            $table->renameColumn('view', 'view_total');
            $table->tinyInteger('store_video_call_type')
                ->nullable()
                ->comment('1: Public, 2: Private')
                ->after('video_call_type');
            $table->tinyInteger('final_video_call_type')
                ->nullable()
                ->comment('1: Public, 2: Private')
                ->after('store_video_call_type');
            $table->renameColumn('video_call_type', 'customer_video_call_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dtb_bookings', function (Blueprint $table) {
            $table->renameColumn('customer_video_call_type', 'video_call_type');
            $table->dropColumn('final_video_call_type');
            $table->dropColumn('store_video_call_type');
            $table->renameColumn('view_total', 'view');
            $table->string('token')
                ->nullable()
                ->comment('')
                ->change();
            $table->dropColumn('customer_note');
            $table->dropColumn('customer_id');
        });
    }
};
