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
        Schema::table('production_plan_details', function (Blueprint $table) {
            // Kita tambahkan kolom delay_qty, tipe integer, default 0
            // Kita taruh posisinya setelah qty_plan agar rapi
            $table->integer('delay_qty')->default(0)->after('qty_plan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('production_plan_details', function (Blueprint $table) {
            // Fungsi rollback (jika migrasi dibatalkan)
            $table->dropColumn('delay_qty');
        });
    }
};