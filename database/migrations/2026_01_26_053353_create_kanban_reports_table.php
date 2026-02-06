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
        Schema::create('kanban_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('code_part'); // Relasi ke master part

            // Data Perhitungan
            $table->integer('qty_delay')->default(0);  // Sisa dari H-1
            $table->integer('qty_target')->default(0); // Plan Hari Ini

            // Data Inputan Shift 1
            $table->string('pic_shift_1')->nullable();
            $table->integer('act_shift_1')->default(0);
            $table->string('lot_shift_1')->nullable(); // Gabungan Th/Bln/Tgl/Frek

            // Data Inputan Shift 2
            $table->string('pic_shift_2')->nullable();
            $table->integer('act_shift_2')->default(0);
            $table->string('lot_shift_2')->nullable();

            $table->timestamps();

            // Mencegah duplikasi data per tanggal & part
            $table->unique(['report_date', 'code_part']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_reports');
    }
};
