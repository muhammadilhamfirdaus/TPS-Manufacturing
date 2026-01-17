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
        Schema::table('production_lines', function (Blueprint $table) {
            // Menambah kolom total_shifts, default 3 (Normalnya 3 shift)
            $table->integer('total_shifts')->default(3)->after('name');
        });
    }

    public function down()
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->dropColumn('total_shifts');
        });
    }
};
