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
        Schema::table('production_plans', function (Blueprint $table) {
            $table->integer('revision')->default(0)->after('status'); // Versi Revisi (0, 1, 2...)
            $table->unsignedBigInteger('original_plan_id')->nullable()->after('revision'); // ID Plan Asli (sebelum revisi)
            // Kita juga akan gunakan kolom 'status' untuk menandai 'HISTORY'
        });
    }

    public function down()
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropColumn(['revision', 'original_plan_id']);
        });
    }
};
