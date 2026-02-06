<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('kanban_reports', function (Blueprint $table) {
            $table->integer('ng_shift_1')->default(0)->after('act_shift_1'); // Jumlah NG Shift 1
            $table->integer('ng_shift_2')->default(0)->after('act_shift_2'); // Jumlah NG Shift 2
            $table->text('keterangan')->nullable()->after('lot_shift_2');    // Catatan Defect
        });
    }

    public function down()
    {
        Schema::table('kanban_reports', function (Blueprint $table) {
            $table->dropColumn(['ng_shift_1', 'ng_shift_2', 'keterangan']);
        });
    }
};
